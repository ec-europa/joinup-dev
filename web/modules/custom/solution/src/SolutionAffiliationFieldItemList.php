<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Defines a field item list class for the solution 'collections' field.
 *
 * In ADMS-AP collections point to solutions. The reverse relation would have
 * been more logical, and this is quite inconvenient, especially for the search
 * index. This field computes the reverse relationship.
 */
class SolutionAffiliationFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if ($this->getEntity()->id()) {
      foreach ($this->getAffiliation() as $delta => $collection_id) {
        $this->list[$delta] = $this->createItem($delta, ['target_id' => $collection_id]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(): void {
    parent::preSave();

    // A solution cannot be saved without having a parent collection.
    if ($this->isEmpty()) {
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $graph_handler */
      $graph_handler = \Drupal::service('sparql.graph_handler');
      // We enforce data integrity only for solutions from the 'official'
      // graphs. Solutions stored in other graphs, might exist, temporary,
      // without a parent collection. The code that is handling such cases is
      // responsible to ensure data integrity. Such a use case is the data
      // federation, where the imported solutions are stored in a 'non-official'
      // graph and solutions are allowed to temporary exist or/and be saved
      // without having a parent collection.
      if (in_array($this->getEntity()->get('graph')->target_id, $graph_handler->getEntityTypeDefaultGraphIds('rdf_entity'))) {
        throw new \Exception("Solution '{$this->getEntity()->id()}' should have a parent collection.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    $solution_id = $this->getEntity()->id();

    $collection_ids = array_map(function (EntityReferenceItem $field_item): string {
      return $field_item->target_id;
    }, $this->list);

    // Optimize when the solution doesn't have yet an ID.
    $existing_collection_ids = $solution_id ? $this->getAffiliation() : [];

    // Update collections where this solution is no more affiliate.
    if ($removed_collection_ids = array_diff($existing_collection_ids, $collection_ids)) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      foreach (Rdf::loadMultiple($removed_collection_ids) as $collection) {
        /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $affiliates */
        $affiliates = $collection->get('field_ar_affiliates');
        $this->removeFieldItemByTargetId($affiliates, $this->getEntity()->id());
        $collection->skip_notification = TRUE;
        $collection->save();
      }
    }

    // Update collections where this solution is newly affiliated.
    if ($new_collection_ids = array_diff($collection_ids, $existing_collection_ids)) {
      $field_value = ['target_id' => $solution_id];
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      foreach (Rdf::loadMultiple($new_collection_ids) as $collection) {
        if ($collection->bundle() !== 'collection') {
          throw new \Exception('Only collections can be referenced in affiliation requests.');
        }
        $collection->get('field_ar_affiliates')->appendItem($field_value);
        $collection->skip_notification = TRUE;
        $collection->save();
      }
    }

    return parent::postSave($update);
  }

  /**
   * Returns the affiliation of the solution host entity.
   *
   * @return string[]
   *   A list of collection IDs where the solution host entity is affiliated.
   */
  protected function getAffiliation(): array {
    return array_values(\Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'collection')
      ->condition('field_ar_affiliates', $this->getEntity()->id())
      ->execute());
  }

  /**
   * Removes a field item given a target ID.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_item_list
   *   An entity reference field item list.
   * @param string $target_id
   *   The target ID for which the field item should be removed.
   */
  protected function removeFieldItemByTargetId(EntityReferenceFieldItemListInterface $field_item_list, string $target_id): void {
    foreach ($field_item_list as $delta => $field_item) {
      if ($field_item->target_id === $target_id) {
        $field_item_list->removeItem($delta);
        return;
      }
    }
  }

}

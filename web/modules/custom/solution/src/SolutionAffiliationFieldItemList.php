<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
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
  protected function computeValue(): void {
    foreach ($this->getAffiliation() as $delta => $collection_id) {
      $this->list[$delta] = $this->createItem($delta, ['target_id' => $collection_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // Update the affiliation only on new solutions.
    if (!$update) {
      $collection_ids = array_map(function (EntityReferenceItem $field_item): string {
        return $field_item->target_id;
      }, $this->list);

      $field_value = ['target_id' => $this->getEntity()->id()];
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      foreach (Rdf::loadMultiple($collection_ids) as $id => $collection) {
        if ($collection->bundle() !== 'collection') {
          throw new \Exception('Only collections can be referenced in affiliation requests.');
        }
        $collection->get('field_ar_affiliates')->appendItem($field_value);
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

}

<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface;

/**
 * Defines a field item list class for the distribution 'parent' field.
 */
class DistributionParentFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue(): void {
    $distribution = $this->getEntity();
    if ($distribution->id() && ($parent_id = $this->getParentId($distribution))) {
      $this->list[0] = $this->createItem(0, ['target_id' => $parent_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(): void {
    parent::preSave();

    $distribution = $this->getEntity();
    /** @var \Drupal\rdf_entity\RdfInterface $parent */
    if ($distribution->get('og_audience')->isEmpty() && isset($this->list[0]) && ($parent = $this->list[0]->entity)) {
      if ($parent->bundle() === 'solution') {
        $audience = $parent->id();
      }
      elseif ($parent->bundle() === 'asset_release' && $parent->get('field_isr_is_version_of')->entity) {
        $audience = $parent->get('field_isr_is_version_of')->target_id;
      }
      else {
        throw new \Exception("The distribution parent should be either a 'solution' or an 'asset_release'; '{$parent->bundle()}' was assigned.");
      }

      // Set the distribution audience.
      $distribution->set('og_audience', $audience);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {
    // Set the parent only for new distributions.
    /** @var \Drupal\rdf_entity\RdfInterface $parent */
    if (!$update && isset($this->list[0]) && ($parent = $this->list[0]->entity)) {
      $parent->skip_notification = TRUE;
      $field_name = $parent->bundle() === 'solution' ? 'field_is_distribution' : 'field_isr_distribution';
      $parent->get($field_name)->appendItem(['target_id' => $this->getEntity()->id()]);
      $parent->save();
    }
    return parent::postSave($update);
  }

  /**
   * Returns the parent of the distribution host entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $distribution
   *   The distribution entity.
   *
   * @return string|null
   *   The parent entity ID.
   *
   * @throws \Exception
   *   When the distribution has more than one parent.
   */
  protected function getParentId(RdfInterface $distribution): ?string {
    $ids = $this->getQuery($distribution)->execute();
    return reset($ids) ?: NULL;
  }

  /**
   * Returns the query used to determine the parent ID.
   *
   * @param \Drupal\rdf_entity\RdfInterface $distribution
   *   The distribution entity.
   *
   * @return \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface
   *   The query used to determine the parent ID.
   */
  protected function getQuery(RdfInterface $distribution): SparqlQueryInterface {
    /** @var \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface $query */
    $query = \Drupal::entityQuery('rdf_entity', 'OR')
      ->condition('field_is_distribution', $distribution->id())
      ->condition('field_isr_distribution', $distribution->id());
    return $query;
  }

}

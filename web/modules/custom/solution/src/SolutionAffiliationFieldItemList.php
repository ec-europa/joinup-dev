<?php

namespace Drupal\solution;

use Drupal\cached_computed_field\Plugin\Field\FieldType\CachedComputedItemTrait;
use Drupal\Core\Field\EntityReferenceFieldItemList;

/**
 * Defines a field item list class for the solution 'collections' field.
 *
 * In ADMS-AP collections point to solutions. The reverse relation would have
 * been more logical, and this is quite inconvenient, especially for the search
 * index. This field computes the reverse relationship.
 */
class SolutionAffiliationFieldItemList extends EntityReferenceFieldItemList {

  use CachedComputedItemTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $collection_ids = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'collection')
      ->condition('field_ar_affiliates', $this->getEntity()->id())
      ->execute();

    foreach (array_values($collection_ids) as $delta => $collection_id) {
      $this->list[$delta] = $this->createItem($delta, ['target_id' => $collection_id]);
    }
  }

}

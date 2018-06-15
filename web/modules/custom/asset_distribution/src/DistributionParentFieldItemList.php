<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\rdf_entity\RdfInterface;

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
    if ($distribution->id()) {
      $this->list[0] = $this->createItem(0, [
        'target_id' => $this->getParentRdfEntity($distribution),
      ]);
    }
  }

  /**
   * Returns the parent of the distribution host entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $distribution
   *   The distribution entity.
   *
   * @return string
   *   The parent entity ID.
   *
   * @throws \Exception
   *   When the distribution has more than one parent.
   */
  protected function getParentRdfEntity(RdfInterface $distribution): string {
    $ids = \Drupal::entityQuery('rdf_entity', 'OR')
      ->condition('field_is_distribution', $distribution->id())
      ->condition('field_isr_distribution', $distribution->id())
      ->execute();

    if (count($ids) > 1) {
      throw new \Exception("More than one parent was found for distribution '{$distribution->label()}'.");
    }

    return reset($ids);
  }

}

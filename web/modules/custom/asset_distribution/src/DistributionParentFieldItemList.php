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
    if ($parent_id = $this->getParentId($distribution)) {
      $this->list[0] = $this->createItem(0, ['target_id' => $parent_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    $distribution = $this->getEntity();
    if ($distribution->isNew() && $this->list[0]->entity) {
      /** @var \Drupal\rdf_entity\RdfInterface $parent */
      if ($parent = $this->list[0]->entity) {
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
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    // Set the parent only for new distributions.
    if (!$update) {
      if ($parent = $this->list[0]->entity) {
        // Update the parent.
        $parent->skip_notification = TRUE;
        $field_name = $parent->bundle() === 'solution' ? 'field_is_distribution' : 'field_isr_distribution';
        $parent->set($field_name, $parent->id())->save();
      }
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
    $ids = \Drupal::entityQuery('rdf_entity', 'OR')
      ->condition('field_is_distribution', $distribution->id())
      ->condition('field_isr_distribution', $distribution->id())
      ->execute();

    if (count($ids) > 1) {
      throw new \Exception("More than one parent was found for distribution '{$distribution->label()}'.");
    }

    return reset($ids) ?: NULL;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\joinup_core\Entity\OutdatedContentInterface;

/**
 * Field item list class for the 'outdated_time' bundle base field.
 *
 * @see joinup_core_entity_bundle_field_info()
 */
class OutdatedContentFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue(): void {
    $entity = $this->getEntity();
    if (!$entity instanceof OutdatedContentInterface) {
      throw new \LogicException(__CLASS__ . ' field item list should be used only for fields on entities implementing ' . OutdatedContentInterface::class);
    }

    $outdated_date = NULL;
    $config = \Drupal::config('joinup_core.outdated_content_threshold');

    // If $threshold is NULL, then the entity will never be outdated.
    if ($threshold = $config->get("{$entity->getEntityTypeId()}.{$entity->bundle()}")) {
      $publication_time = $entity->getPublicationTime();
    }

    // If $publication_time is NULL, then the entity has never been published,
    // thus it cannot be outdated.
    if (!empty($publication_time)) {
      $published_at = new \DateTime("@$publication_time");
      $outdated_date = $published_at->add(new \DateInterval("P{$threshold}Y"))->getTimestamp();
    }

    $this->list[0] = $this->createItem(0, $outdated_date);
  }

}

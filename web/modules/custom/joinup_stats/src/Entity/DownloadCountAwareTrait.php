<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Shared code for entities that provide download count statistics.
 */
trait DownloadCountAwareTrait {

  /**
   * {@inheritdoc}
   */
  public function getDownloadCount(): int {
    $field_name = self::JOINUP_STATS_FIELDS[DownloadCountAwareInterface::class];
    return $this->$field_name->entity->getDownloadCount();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

/**
 * Shared code for entities that provide visit count statistics.
 */
trait VisitCountAwareTrait {

  /**
   * {@inheritdoc}
   */
  public function getVisitCount(): int {
    $field_name = self::JOINUP_STATS_FIELDS[VisitCountAwareInterface::class];
    return $this->$field_name->entity->getVisitCount();
  }

}

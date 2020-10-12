<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for entities that provide statistics of their usage.
 */
interface StatisticsAwareInterface extends ContentEntityInterface {

  /**
   * A list of fields that contain statistical information.
   *
   * These are keyed by the specific interface that designates the availability
   * of the field.
   */
  const STATISTICS_FIELDS = [
    DownloadCountAwareInterface::class => 'download_count',
    VisitCountAwareInterface::class => 'visit_count',
  ];

  /**
   * Creates the meta entities that provides statistical information.
   *
   * @return \Drupal\meta_entity\Entity\MetaEntityInterface[]
   *   The newly created meta entities.
   */
  public function createStatisticsMetaEntities(): array;

}

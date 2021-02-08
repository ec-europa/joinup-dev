<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for entities that provide statistics of their usage.
 */
interface StatisticsAwareInterface extends ContentEntityInterface {

  /**
   * Returns the field names that are referencing statistics.
   *
   * @return array
   */
  public function getStatisticsFieldNames(): array;

  /**
   * Creates the meta entities that provides statistical information.
   *
   * @return \Drupal\meta_entity\Entity\MetaEntityInterface[]
   *   The newly created meta entities.
   */
  public function createStatisticsMetaEntities(): array;

}

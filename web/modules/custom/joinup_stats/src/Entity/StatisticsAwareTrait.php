<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

use Drupal\joinup_bundle_class\Exception\MetaEntityAlreadyExistsException;

/**
 * Shared code for entities that provide statistics of their usage.
 *
 * @todo Once we are on PHP 7.3 the JoinupBundleClassMetaEntityTrait
 *   should be included here.
 */
trait StatisticsAwareTrait {

  /**
   * {@inheritdoc}
   */
  public function getStatisticsFieldNames(): array {
    return self::JOINUP_STATS_FIELDS;
  }

  /**
   * {@inheritdoc}
   */
  public function createStatisticsMetaEntities(): array {
    $entities = [];
    foreach ($this->getStatisticsFieldNames() as $field_name) {
      try {
        $entities[] = $this->createMetaEntity($field_name);
      }
      catch (MetaEntityAlreadyExistsException $e) {
        // The meta entity already exists. This is unexpected but not a reason
        // to abort the request with a fatal error. Log a warning.
        \Drupal::logger('joinup_stats')->warning($e->getMessage());
      }
    }

    return $entities;
  }

}

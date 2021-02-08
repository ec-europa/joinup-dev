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
  public function createStatisticsMetaEntities(): array {
    /** @var \Drupal\meta_entity\MetaEntityRepositoryInterface $repository */
    $repository = \Drupal::service('meta_entity.repository');
    $field_names = $repository->getReverseReferenceFieldNames($this->getEntityTypeId(), $this->bundle());

    // Filter out all field names that do not contain statistical data.
    $field_names = array_intersect($field_names, StatisticsAwareInterface::STATISTICS_FIELDS);

    $entities = [];
    foreach ($field_names as $field_name) {
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

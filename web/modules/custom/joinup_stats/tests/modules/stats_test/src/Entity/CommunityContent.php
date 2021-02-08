<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareInterface;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\joinup_stats\Entity\VisitCountAwareInterface;
use Drupal\node\Entity\Node;

/**
 * Test bundle class for community content entities.
 */
class CommunityContent extends Node implements StatisticsAwareInterface {

  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;
  use StatisticsAwareTrait;

  /**
   * Fields populated with statistical information by the joinup_stats module.
   */
  const JOINUP_STATS_FIELDS = [
    VisitCountAwareInterface::class => 'visit_count',
  ];

}

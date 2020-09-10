<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareInterface;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\node\Entity\Node;

/**
 * Test bundle class for community content entities.
 */
class CommunityContent extends Node implements StatisticsAwareInterface {

  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;
  use StatisticsAwareTrait;

}

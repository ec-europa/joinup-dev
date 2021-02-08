<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareInterface;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Test bundle class for asset distributions.
 */
class AssetDistribution extends Rdf implements StatisticsAwareInterface {

  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;
  use StatisticsAwareTrait;

}

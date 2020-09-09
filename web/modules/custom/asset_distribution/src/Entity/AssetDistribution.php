<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityAccessTrait;
use Drupal\joinup_group\Entity\GroupContentTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\solution\Entity\SolutionContentTrait;

/**
 * Bundle class for the 'asset_distribution' bundle.
 *
 * @todo Once we are on PHP 7.3 we should no longer include
 *   JoinupBundleClassMetaEntityAccessTrait.
 */
class AssetDistribution extends Rdf implements AssetDistributionInterface {

  use GroupContentTrait;
  use SolutionContentTrait;
  use JoinupBundleClassMetaEntityAccessTrait;
  use StatisticsAwareTrait;

}

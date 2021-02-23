<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats_test\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_stats\Entity\DownloadCountAwareInterface;
use Drupal\joinup_stats\Entity\DownloadCountAwareTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Test bundle class for asset distributions.
 */
class AssetDistribution extends Rdf implements DownloadCountAwareInterface {

  use DownloadCountAwareTrait;
  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;

  /**
   * Fields populated with statistical information by the joinup_stats module.
   */
  const JOINUP_STATS_FIELDS = [
    DownloadCountAwareInterface::class => 'download_count',
  ];

}

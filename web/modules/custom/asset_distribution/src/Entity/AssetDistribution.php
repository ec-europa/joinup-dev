<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\asset_distribution\Exception\MissingDistributionParentException;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_group\Entity\GroupContentTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\solution\Entity\SolutionContentTrait;

/**
 * Bundle class for the 'asset_distribution' bundle.
 *
 * @todo Once we are on PHP 7.3 we should no longer include
 *   JoinupBundleClassMetaEntityTrait.
 */
class AssetDistribution extends Rdf implements AssetDistributionInterface {

  use GroupContentTrait;
  use SolutionContentTrait;
  use JoinupBundleClassMetaEntityTrait;
  use StatisticsAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    /** @var \Drupal\asset_distribution\DistributionParentFieldItemList $field */
    $field = $this->get('parent');

    /** @var \Drupal\asset_release\Entity\AssetReleaseInterface|\Drupal\solution\Entity\SolutionInterface $parent */
    if ($field->isEmpty() || !($parent = $field->entity)) {
      throw new MissingDistributionParentException();
    }

    return $parent;
  }

}

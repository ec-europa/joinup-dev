<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\asset_distribution\Exception\MissingDistributionParentException;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_group\Entity\GroupContentTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\solution\Entity\SolutionContentTrait;
use Drupal\solution\Entity\SolutionInterface;

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
      // During normal operation every distribution should have a parent entity,
      // so the only way a parent can be missing is because of an unexpected
      // condition occurring at runtime, for example if a data store goes
      // offline.
      throw new MissingDistributionParentException();
    }

    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function isStandalone(): bool {
    return $this->getParent() instanceof SolutionInterface;
  }

}

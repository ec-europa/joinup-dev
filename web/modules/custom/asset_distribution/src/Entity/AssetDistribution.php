<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionContentTrait;

/**
 * Bundle class for the 'asset_distribution' bundle.
 */
class AssetDistribution extends Rdf implements AssetDistributionInterface {

  use JoinupBundleClassFieldAccessTrait;
  use SolutionContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup(): RdfInterface {
    $field_item = $this->getFirstItem(OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    if (!$field_item || $field_item->isEmpty()) {
      throw new MissingGroupException();
    }
    return $field_item->entity;
  }

}

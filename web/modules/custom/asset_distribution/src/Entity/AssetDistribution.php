<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\joinup_group\Entity\GroupContentTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\solution\Entity\SolutionContentTrait;

/**
 * Bundle class for the 'asset_distribution' bundle.
 */
class AssetDistribution extends Rdf implements AssetDistributionInterface {

  use GroupContentTrait;
  use SolutionContentTrait;

}

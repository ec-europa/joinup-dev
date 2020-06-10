<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionContentInterface;

/**
 * Interface for asset distribution entities in Joinup.
 */
interface AssetDistributionInterface extends RdfInterface, CollectionContentInterface, SolutionContentInterface {

}

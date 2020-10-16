<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionContentInterface;

/**
 * Interface for asset release entities in Joinup.
 */
interface AssetReleaseInterface extends RdfInterface, CollectionContentInterface, SolutionContentInterface, EntityWorkflowStateInterface {

  /**
   * Checks whether this release is the latest release of the parent solution.
   *
   * @return bool
   *   If this release is the latest release in the parent solution.
   */
  public function isLatestRelease(): bool;

}

<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\asset_release\Entity\AssetReleaseInterface;
use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_bundle_class\ShortIdInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for solution entities in Joinup.
 */
interface SolutionInterface extends RdfInterface, CollectionContentInterface, PinnableGroupContentInterface, EntityWorkflowStateInterface, GroupInterface, ShortIdInterface {

  /**
   * Returns the latest release ID of this solution, if any.
   *
   * @return string|null
   *   The latest release ID of this solution, if any.
   */
  public function getLatestReleaseId(): ?string;

  /**
   * Returns the latest release of this solution, if any.
   *
   * @return \Drupal\asset_release\Entity\AssetReleaseInterface|null
   *   The latest release of this solution, if any.
   */
  public function getLatestRelease(): ?AssetReleaseInterface;

}

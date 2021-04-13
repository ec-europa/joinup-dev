<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_stats\Entity\DownloadCountAwareInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionContentInterface;

/**
 * Interface for asset distribution entities in Joinup.
 */
interface AssetDistributionInterface extends RdfInterface, CollectionContentInterface, SolutionContentInterface, DownloadCountAwareInterface {

  /**
   * Return the distribution's parent, either a release or a solution.
   *
   * @return \Drupal\asset_distribution\Entity\DistributionsParentInterface
   *   The parent entity, either a release or a solution.
   *
   * @throws \Drupal\asset_distribution\Exception\MissingDistributionParentException
   *   If the parent entity reference is missing or refers to an invalid entity.
   *   This should not occur during normal operation and calling code does not
   *   need to catch this exception. This will only be thrown in case something
   *   is seriously wrong, e.g. if the database is down.
   */
  public function getParent(): DistributionsParentInterface;

  /**
   * Checks whether the distribution parent is a solution rather than a release.
   *
   * @return bool
   *   Whether the distribution parent is a solution rather than a release.
   */
  public function isStandalone(): bool;

}

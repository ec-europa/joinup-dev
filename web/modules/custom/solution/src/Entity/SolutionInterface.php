<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\asset_distribution\Entity\DistributionsParentInterface;
use Drupal\asset_release\Entity\AssetReleaseInterface;
use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for solution entities in Joinup.
 */
interface SolutionInterface extends RdfInterface, CollectionContentInterface, EntityPublicationTimeInterface, EntityWorkflowStateInterface, FeaturedContentInterface, PinnableGroupContentInterface, GroupInterface, DistributionsParentInterface {

  /**
   * Returns teh child releases.
   *
   * @return \Drupal\asset_release\Entity\AssetReleaseInterface[]
   *   An array of child releases, keyed by their ID.
   */
  public function getReleases(): array;

  /**
   * Returns a list of child release IDs.
   *
   * @return string[]
   *   A list of release IDs.
   */
  public function getReleaseIds(): array;

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

  /**
   * Returns the communities this solution is affiliated with.
   *
   * @return \Drupal\collection\Entity\CollectionInterface[]
   *   The affiliated communities, keyed by collection ID.
   */
  public function getAffiliatedCommunities(): array;

  /**
   * Returns the IDs of the communities this solution is affiliated with.
   *
   * @return string[]
   *   The affiliated collection IDs.
   */
  public function getAffiliatedCollectionIds(): array;

}

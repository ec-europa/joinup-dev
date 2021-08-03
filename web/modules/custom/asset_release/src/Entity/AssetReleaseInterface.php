<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Entity;

use Drupal\asset_distribution\Entity\DistributionsParentInterface;
use Drupal\collection\Entity\CommunitiesContentInterface;
use Drupal\joinup_bundle_class\LogoInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\solution\Entity\SolutionContentInterface;

/**
 * Interface for asset release entities in Joinup.
 */
interface AssetReleaseInterface extends RdfInterface, CommunitiesContentInterface, SolutionContentInterface, EntityPublicationTimeInterface, EntityWorkflowStateInterface, LogoInterface, DistributionsParentInterface {

  /**
   * Checks whether this release is the latest release of the parent solution.
   *
   * @return bool
   *   If this release is the latest release in the parent solution.
   */
  public function isLatestRelease(): bool;

  /**
   * Returns the release version number.
   *
   * @return string|null
   *   The release version number.
   *
   * @todo The return value should be enforced to `string` in ISAICP-6217.
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6217
   */
  public function getVersion(): ?string;

}

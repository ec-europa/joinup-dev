<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_bundle_class\ShortIdInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\joinup_workflow\ArchivableEntityInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for collection entities in Joinup.
 */
interface CollectionInterface extends RdfInterface, EntityPublicationTimeInterface, EntityWorkflowStateInterface, GroupInterface, FeaturedContentInterface, ShortIdInterface, ArchivableEntityInterface {

  /**
   * Returns the solutions that are affiliated with this collection.
   *
   * @param bool $published
   *   When TRUE, only published solutions will be returned. Defaults to TRUE.
   *
   * @return \Drupal\solution\Entity\SolutionInterface[]
   *   The solutions.
   */
  public function getSolutions(bool $published = TRUE): array;

  /**
   * Returns the IDs of the solutions that are affiliated with this collection.
   *
   * @param bool $published
   *   When TRUE, only published solutions will be returned. Defaults to TRUE.
   *
   * @return string[]
   *   The solutions.
   */
  public function getSolutionIds(bool $published = TRUE): array;

}

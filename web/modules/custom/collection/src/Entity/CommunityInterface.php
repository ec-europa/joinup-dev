<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\joinup_workflow\ArchivableEntityInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for collection entities in Joinup.
 */
interface CommunityInterface extends RdfInterface, EntityPublicationTimeInterface, EntityWorkflowStateInterface, GroupInterface, FeaturedContentInterface, ArchivableEntityInterface {

  /**
   * Returns the solutions that are affiliated with this collection.
   *
   * @param bool $only_published
   *   (optional) Whether to return only published solutions. Defaults to FALSE.
   *
   * @return \Drupal\solution\Entity\SolutionInterface[]
   *   The solutions.
   */
  public function getSolutions(bool $only_published = FALSE): array;

  /**
   * Returns the IDs of the solutions that are affiliated with this collection.
   *
   * @param bool $only_published
   *   (optional) Whether to return only published solutions. Defaults to FALSE.
   *
   * @return string[]
   *   The solutions.
   */
  public function getSolutionIds(bool $only_published = FALSE): array;

  /**
   * Returns the collection glossary settings.
   *
   * @return array
   *   The collection glossary settings.
   *
   * @throws \Exception
   *   When the collection misses a 'collection_settings' meta entity.
   */
  public function getGlossarySettings(): array;

  /**
   * Whether or not the collection is closed.
   *
   * In a closed collection users can only become members after being approved
   * by a facilitator. The user memberships will be initially in pending state.
   *
   * @return bool
   *   TRUE if the collection is closed, FALSE if it is open.
   */
  public function isClosed(): bool;

}

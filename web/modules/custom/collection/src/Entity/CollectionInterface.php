<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_bundle_class\ShortIdInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationDateInterface;
use Drupal\joinup_workflow\ArchivableEntityInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for collection entities in Joinup.
 */
interface CollectionInterface extends RdfInterface, EntityPublicationDateInterface, EntityWorkflowStateInterface, GroupInterface, FeaturedContentInterface, ShortIdInterface, ArchivableEntityInterface {

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

}

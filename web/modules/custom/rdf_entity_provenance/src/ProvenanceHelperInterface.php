<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance;

use Drupal\rdf_entity\RdfInterface;

/**
 * Provides helper methods to fetch, check and update provenance data.
 */
interface ProvenanceHelperInterface {

  /**
   * Loads or creates a provenance record for the passed RDF entity ID.
   *
   * @param string $id
   *   The rdf id that the provenance activity describes.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The provenance activity related to the rdf entity passed.
   */
  public function loadOrCreateEntityActivity(string $id): RdfInterface;

  /**
   * Loads or creates provenance entities for the passed RDF entity IDs.
   *
   * @param string[] $ids
   *   A list of RDF entity IDs.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   An associative array keyed by the referred entity and having the
   *   provenance entity object as value.
   */
  public function loadOrCreateEntitiesActivity(array $ids): array;

  /**
   * Retrieves the provenance activity related to an rdf entity.
   *
   * @param string $id
   *   The rdf id that the provenance activity describes.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The provenance activity related to the rdf entity passed, or null if no
   *   record exists.
   */
  public function loadProvenanceActivity(string $id): ?RdfInterface;

  /**
   * Loads a list of provenance entities given a list of referred entities.
   *
   * @param string[] $ids
   *   A list of referred entities.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   An associative array keyed by the referred entity and having the
   *   provenance entity object as value.
   */
  public function loadProvenanceActivities(array $ids): array;

  /**
   * Retrieves the associated entity of the activity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $activity
   *   The provenance activity rdf entity.
   *
   * @return RdfInterface|null
   *   The rdf entity the activity is associated with.
   */
  public function loadActivityAssociatedEntity(RdfInterface $activity): ?RdfInterface;

}

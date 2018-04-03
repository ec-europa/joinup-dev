<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance;

use Drupal\rdf_entity\RdfInterface;

/**
 * Provides helper methods to fetch, check and update provenance data.
 */
interface ProvenanceHelperInterface {

  /**
   * Checks whether the the entity has provenance records.
   *
   * If an entity has provenance records it means that it is associated with an
   * external source and thus has been federated.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity.
   *
   * @return bool
   *   Whether the entity has provenance record associated and thus, is
   *   federated.
   */
  public function isFederated(RdfInterface $rdf_entity): bool;

  /**
   * Loads or creates a provenance record for the passed rdf entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The provenance activity related to the rdf entity passed.
   */
  public function getProvenanceActivity(RdfInterface $rdf_entity): RdfInterface;

  /**
   * Retrieves the provenance activity related to an rdf entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The rdf entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The provenance activity related to the rdf entity passed, or null if no
   *   record exists.
   */
  public function loadProvenanceActivity(RdfInterface $rdf_entity): ?RdfInterface;

}

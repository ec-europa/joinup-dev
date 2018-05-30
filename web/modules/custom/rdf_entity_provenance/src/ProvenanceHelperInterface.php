<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity_provenance;

use Drupal\rdf_entity\RdfInterface;

/**
 * Provides helper methods to fetch, check and update provenance data.
 */
interface ProvenanceHelperInterface {

  /**
   * Loads or creates a provenance record for the passed rdf entity.
   *
   * @param string $id
   *   The rdf id that the provenance activity describes.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The provenance activity related to the rdf entity passed.
   */
  public function getProvenanceByReferredEntity(string $id): RdfInterface;

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

}

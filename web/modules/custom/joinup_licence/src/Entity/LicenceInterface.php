<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for licence entities in Joinup.
 */
interface LicenceInterface extends RdfInterface {

  /**
   * Returns the RDF ID of the associated SPDX licence.
   *
   * @return string|null
   *   The RDF ID of the SPDX licence, or NULL if no SPDX licence is associated
   *   with the licence entity.
   */
  public function getSpdxLicenceRdfId(): ?string;

}

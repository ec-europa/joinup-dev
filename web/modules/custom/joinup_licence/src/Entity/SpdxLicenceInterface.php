<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for SPDX licence entities.
 *
 * This RDF entity bundle type is defined by the SPDX module, but since the
 * bundle classes are not yet available in vanilla Drupal we define them inside
 * Joinup rather than in the contributed module.
 */
interface SpdxLicenceInterface extends RdfInterface {

  /**
   * Returns the SPDX licence ID, such as 'Apache-2.0'.
   *
   * @return string|null
   *   The SPDX licence ID, or NULL if it is not set.
   */
  public function getLicenceId(): ?string;

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for licence entities in Joinup.
 */
interface LicenceInterface extends RdfInterface {

  /**
   * Returns the legal types to which this licence conforms.
   *
   * @return \Drupal\joinup_licence\Entity\LicenceLegalTypeInterface[]
   *   The legal types.
   */
  public function getLegalTypes(): array;

  /**
   * Returns whether or not the licence conforms to the given legal type.
   *
   * @param string $category_label
   *   The legal type category label, such as 'Can', 'Must', 'Cannot', etc.
   * @param string $label
   *   The legal type label, such as 'Use/reproduce', 'Distribute', etc.
   *
   * @return bool
   *   Whether or not the licence conforms to the legal type.
   */
  public function hasLegalType(string $category_label, string $label): bool;

  /**
   * Returns the associated SPDX licence entity.
   *
   * @return \Drupal\joinup_licence\Entity\SpdxLicenceInterface|null
   *   The SPDX licence entity, or NULL if none is associated with the licence
   *   entity.
   */
  public function getSpdxLicenceEntity(): ?SpdxLicenceInterface;

  /**
   * Returns the ID of the associated SPDX licence, such as 'Apache-2.0'.
   *
   * @return string|null
   *   The ID of the SPDX licence, or NULL if no SPDX licence is associated with
   *   the licence entity.
   */
  public function getSpdxLicenceId(): ?string;

  /**
   * Returns the RDF ID of the associated SPDX licence.
   *
   * @return string|null
   *   The RDF ID of the SPDX licence, or NULL if no SPDX licence is associated
   *   with the licence entity.
   */
  public function getSpdxLicenceRdfId(): ?string;

}

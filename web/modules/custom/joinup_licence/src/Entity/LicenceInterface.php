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

  /**
   * Checks if the current licence can be redistributed under another licence.
   *
   * This allows to verify whether code or data which is distributed under the
   * current licence can be used in a project which is going to be distributed
   * under the given licence.
   *
   * Note that this comes with some legal caveats, and the result of this method
   * should be interpreted alongside the accompanying compatibility document
   * that contains more details about how the redistribution can / should take
   * place.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $redistribute_as_licence
   *   The licence under which the current code or data is going to be
   *   redistributed.
   *
   * @return bool
   *   TRUE if the current licence can be redistributed as the given licence.
   *   Some restrictions apply, please check the accompanying compatibility
   *   document for more information.
   *
   * @see \Drupal\joinup_licence\Entity\LicenceInterface::getCompatibilityDocumentId()
   */
  public function isCompatibleWith(LicenceInterface $redistribute_as_licence): bool;

  /**
   * Returns a document ID that details how the licence can be redistributed.
   *
   * This document contains advice how code or data which is distributed under
   * the current licence can be used in a project which is going to be
   * distributed under the passed in licence.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $redistribute_as_licence
   *   The licence under which the current code or data is going to be
   *   redistributed.
   *
   * @return string|null
   *   The document ID of the compatibility document that contains the requested
   *   information. If the licences are not compatible NULL is returned.
   */
  public function getCompatibilityDocumentId(LicenceInterface $redistribute_as_licence): ?string;

}

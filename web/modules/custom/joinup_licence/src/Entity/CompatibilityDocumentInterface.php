<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for the Compatibility Document entity type.
 */
interface CompatibilityDocumentInterface extends ContentEntityInterface {

  /**
   * Creates missing compatibility documents.
   *
   * There should be one compatibility document for each compatibility rule.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the CompatibilityDocument entity type is ill-defined.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the CompatibilityDocument entity type is not defined.
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs during the creation of a compatibility
   *   document.
   */
  public static function populate(): void;

  /**
   * Sets the licence of the component that will be included in the project.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $licence
   *   The licence to set.
   *
   * @return \Drupal\joinup_licence\Entity\CompatibilityDocumentInterface
   *   The compatibility document, for chaining.
   */
  public function setUseLicence(LicenceInterface $licence): CompatibilityDocumentInterface;

  /**
   * Sets the licence under which the new, combined, project will be released.
   *
   * @param \Drupal\joinup_licence\Entity\LicenceInterface $licence
   *   The licence to set.
   *
   * @return \Drupal\joinup_licence\Entity\CompatibilityDocumentInterface
   *   The compatibility document, for chaining.
   */
  public function setRedistributeAsLicence(LicenceInterface $licence): CompatibilityDocumentInterface;

}

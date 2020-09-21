<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\taxonomy\TermInterface;

/**
 * Interface for licence legal type terms in Joinup.
 */
interface LicenceLegalTypeInterface extends TermInterface {

  /**
   * Returns the category to which the legal type belongs.
   *
   * These are the root level terms such as 'Can', 'Must', 'Cannot' etc.
   *
   * @return \Drupal\joinup_licence\Entity\LicenceLegalTypeInterface|null
   *   The root level category, or NULL if the term doesn't have a root level
   *   category (e.g. because it is a root level category itself).
   */
  public function getCategory(): ?LicenceLegalTypeInterface;

}

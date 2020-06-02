<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

/**
 * Interface for bundle classes that have a short ID field.
 */
interface ShortIdInterface {

  /**
   * Returns the short ID.
   *
   * @return string|null
   *   The short ID, or NULL if the short ID is not set.
   */
  public function getShortId(): ?string;

  /**
   * Whether or not the short ID has been set.
   *
   * @return bool
   *   TRUE if the short ID is set, FALSE otherwise.
   */
  public function hasShortId(): bool;

}

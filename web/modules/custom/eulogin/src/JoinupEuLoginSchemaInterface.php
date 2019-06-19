<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

/**
 * Provides an interface for the 'joinup_eulogin.schema' service.
 */
interface JoinupEuLoginSchemaInterface {

  /**
   * Parses the schema and updates the stored data.
   *
   * @return bool
   *   TRUE if update occurred, FALSE if it's already up-to-date.
   */
  public function updateSchema(): bool;

}

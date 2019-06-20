<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

/**
 * Provides an interface for the 'joinup_eulogin.schema_updater' service.
 */
interface JoinupEuLoginSchemaUpdaterInterface {

  /**
   * Parses the schema and updates the stored data.
   *
   * @return bool
   *   TRUE if update occurred, FALSE if it's already up-to-date.
   */
  public function update(): bool;

}

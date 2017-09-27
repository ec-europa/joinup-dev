<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Site\Settings;

/**
 * Methods to help circumvent read only config during testing.
 *
 * When the Config Read Only module is enabled it is no longer possible to
 * change configuration, but sometimes this is required during testing, for
 * example to enable temporary test services, or to test different variations of
 * a certain feature.
 */
trait ConfigReadOnlyTrait {

  /**
   * Temporarily disables read only configuration.
   *
   * Make sure to call restoreReadOnlyConfig() after making the necessary config
   * changes.
   *
   * @param int $timeout
   *   The timeout in seconds. Defaults to 2 seconds.
   */
  public function bypassReadOnlyConfig($timeout = 2) {
    // Skip this if the Read Only Config functionality is not active.
    if (!Settings::get('config_readonly')) {
      return;
    }

    // Pretend we are importing config by setting the semaphore of the
    // configuration importer.
    // @see \Drupal\config_readonly\Config\ConfigReadonlyStorage::checkLock()
    /** @var \Drupal\Core\Lock\LockBackendInterface $lock */
    $lock = \Drupal::service('lock');
    $lock->acquire(ConfigImporter::LOCK_NAME, $timeout);
  }

  /**
   * Restores the read only configuration functionality if available.
   */
  public function restoreReadOnlyConfig() {
    // Skip this if the Read Only Config functionality is not active.
    if (!Settings::get('config_readonly')) {
      return;
    }

    /** @var \Drupal\Core\Lock\LockBackendInterface $lock */
    $lock = \Drupal::service('lock');
    $lock->release(ConfigImporter::LOCK_NAME);
  }

}

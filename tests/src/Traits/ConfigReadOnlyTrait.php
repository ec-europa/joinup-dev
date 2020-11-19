<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

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
   * The initial state of config_readonly.
   *
   * @var bool
   */
  protected static $isConfigReadonlyEnabled;

  /**
   * Temporarily disables read only configuration.
   *
   * Make sure to call restoreReadOnlyConfig() after making the necessary config
   * changes.
   */
  public static function bypassReadOnlyConfig(): void {
    static::checkConfigReadOnlyKillSwitch();

    if (!isset(static::$isConfigReadonlyEnabled)) {
      // Save the initial state of config_readonly kill-switch.
      static::$isConfigReadonlyEnabled = !file_exists(getcwd() . '/../disable-config-readonly');
    }

    touch(DRUPAL_ROOT . '/../disable-config-readonly');
    // Ensure the new value also for the current request.
    new Settings(['config_readonly' => FALSE] + Settings::getAll());
  }

  /**
   * Restores the read only configuration functionality if available.
   */
  public static function restoreReadOnlyConfig(): void {
    // Restore as enabled only if initially has been enabled. This allows to
    // keep config_readonly disabled on a local development environment (i.e.
    // where the Task Runner config `config_readonly` was set to `false`), after
    // the tests had finished.
    if (static::$isConfigReadonlyEnabled) {
      /** @var \Drupal\Core\File\FileSystemInterface $file_system */
      $file_system = \Drupal::service('file_system');
      $file_system->unlink(DRUPAL_ROOT . '/../disable-config-readonly');
      // Ensure the new value also for the current request.
      new Settings(['config_readonly' => TRUE] + Settings::getAll());
    }
  }

  /**
   * Checks if the `$settings['config_readonly']` kill-switch exists.
   *
   * @throws \Exception
   *   If the kill-switch is missed.
   */
  protected static function checkConfigReadOnlyKillSwitch(): void {
    /** @var \Drupal\Core\DrupalKernelInterface $kernel */
    $kernel = \Drupal::service('kernel');
    $site_path = $kernel->getSitePath();
    $needle = "\$settings['config_readonly'] = !file_exists(getcwd() . '/../disable-config-readonly');";
    $settings_php = file_get_contents("{$site_path}/settings.php");
    if (strpos($settings_php, $needle) === FALSE) {
      throw new \Exception("The following line is missing from web/sites/default/settings.php\n$needle");
    }
  }

}

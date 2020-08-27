<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

/**
 * Reusable code for inspecting and preparing mail collector configuration.
 */
trait MailConfigTrait {

  /**
   * A list of overridable mail configurations.
   *
   * @var string[]
   */
  protected static $mailOverridableConfigurations = [
    'system.mail' => 'interface.default',
    'mailsystem.settings' => 'defaults.sender',
  ];

  /**
   * Checks if configs are overridden in settings.php, settings.override.php.
   *
   * @throws \Exception
   *   When mail configurations were overridden.
   */
  protected function checkMailConfigOverride(): void {
    $config_factory = \Drupal::configFactory();
    foreach (static::$mailOverridableConfigurations as $config_name => $config_path) {
      if ($config_factory->get($config_name)->hasOverrides($config_path)) {
        throw new \Exception("Cannot inspect emails since '{$config_name}:{$config_path}' is overridden in settings.php or settings.override.php.");
      }
    }
  }

  /**
   * Clears the config values from both cache and $_GLOBALS array.
   *
   * This ensures that when values are overridden or changed in the settings.php
   * file during the test run, the values are being read anew from storage.
   * Config factory sets overrides both in cache and in the $_GLOBALS array so
   * cleaning them would require to remove them from both indexes.
   *
   * When we are changing the settings values before running behat tests, the
   * values are already loaded into cache and $_GLOBALS so checking for
   * overrides will still fail.
   */
  protected function clearConfigValuesCache(): void {
    $config_factory = \Drupal::configFactory();
    foreach (static::$mailOverridableConfigurations as $config_name => $config_path) {
      $config_factory->reset($config_name);
      if (isset($GLOBALS['config'][$config_name])) {
        unset($GLOBALS['config'][$config_name]);
      }
    }
  }

  /**
   * Checks if the test mail collector is currently used.
   *
   * @return bool
   *   TRUE if the testing mail collector is used.
   */
  protected function isTestMailCollectorUsed(): bool {
    $config_factory = \Drupal::configFactory();
    $is_test_mail_collector_used = TRUE;
    foreach (static::$mailOverridableConfigurations as $config_name => $config_path) {
      if ($config_factory->get($config_name)->get($config_path) !== 'test_mail_collector') {
        $is_test_mail_collector_used = FALSE;
        break;
      }
    }
    return $is_test_mail_collector_used;
  }

}

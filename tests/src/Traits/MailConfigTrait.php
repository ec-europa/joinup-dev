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
  protected static function checkMailConfigOverride(): void {
    $config_factory = \Drupal::configFactory();
    foreach (static::$mailOverridableConfigurations as $config_name => $config_path) {
      if ($config_factory->get($config_name)->hasOverrides($config_path)) {
        $message = "Cannot inspect emails since '{$config_name}:{$config_path}' is overridden in settings.php or settings.override.php.";
        \Drupal::logger('test')->error($message);
        throw new \Exception($message);
      }
    }
  }

  /**
   * Checks if the test mail collector is currently used.
   *
   * @return bool
   *   TRUE if the testing mail collector is used.
   */
  protected static function isTestMailCollectorUsed(): bool {
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

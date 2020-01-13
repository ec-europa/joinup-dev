<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

/**
 * Provide reusable code to switch the site's error level reporting in tests.
 */
trait ErrorLevelTrait {

  /**
   * The original error level.
   *
   * @var string
   */
  protected static $originalErrorLevel;

  /**
   * Sets the site's error logging verbosity.
   *
   * @param string|null $error_level
   *   (optional) The error level. If not passed, the original error level is
   *   restored.
   *
   * @throws \Exception
   *   When ConfigReadOnlyTrait trait is not used.
   */
  public static function setSiteErrorLevel(string $error_level = NULL) {
    if (!method_exists(static::class, 'bypassReadOnlyConfig')) {
      throw new \LogicException('This trait should be used together with ConfigReadOnlyTrait.');
    }

    $config = \Drupal::configFactory()->getEditable('system.logging');

    $current_error_level = $config->get('error_level');
    if (!isset(static::$originalErrorLevel)) {
      static::$originalErrorLevel = $current_error_level;
    }

    $error_level = $error_level ?: static::$originalErrorLevel;
    if ($current_error_level !== $error_level) {
      static::bypassReadOnlyConfig();
      $config->set('error_level', $error_level)->save();
      static::restoreReadOnlyConfig();
    }
  }

}

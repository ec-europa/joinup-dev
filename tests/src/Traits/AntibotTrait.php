<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

/**
 * Reusable code that allows switching Antibot functionality on/off.
 */
trait AntibotTrait {

  use ConfigReadOnlyTrait;

  /**
   * The current list of form IDs protected by Antibot.
   *
   * @var string[]
   */
  protected static $currentAntibotFormIds;

  /**
   * Disables the Antibot functionality.
   */
  protected static function disableAntibot(): void {
    $antibot_settings = \Drupal::configFactory()->getEditable('antibot.settings');
    // Save the current form IDs protected by Antibot.
    static::$currentAntibotFormIds = $antibot_settings->get('form_ids');
    // Unprotect all forms for the scope of this test. Antibot is blocking all
    // form submissions when javascript is disabled. As most of the tests are
    // running without javascript, we disable Antibot for all forms during the
    // tests.
    static::bypassReadOnlyConfig();
    $antibot_settings->set('form_ids', [])->save();
    static::restoreReadOnlyConfig();
  }

  /**
   * Restores the Antibot functionality.
   */
  protected static function restoreAntibot(): void {
    static::bypassReadOnlyConfig();
    // Restore the list of form IDs to be protected by Antibot.
    \Drupal::configFactory()
      ->getEditable('antibot.settings')
      ->set('form_ids', static::$currentAntibotFormIds)
      ->save();
    static::restoreReadOnlyConfig();
  }

}

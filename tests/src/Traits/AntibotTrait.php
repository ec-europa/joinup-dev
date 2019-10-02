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

    // Workaround Behat tests static cache persistence issue. Even all forms are
    // tagged with 'config:antibot.settings' cache tag and saving Antibot
    // settings will call the cache tags invalidator, as Behat tests are running
    // in a single, long requests, the cache tags invalidator checksum service
    // is statically caching the tags. We have to reset the service internal
    // cache prior saving the settings, so that the subsequent cache
    // invalidation to be in effect. Note that this is not needed, in a real
    // scenario, where the cache tags invalidator checksum service internal
    // cache is cleared at the end of each request.
    \Drupal::service('cache_tags.invalidator.checksum')->reset();

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

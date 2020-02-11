<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\joinup\Traits\AntibotTrait;
use Drupal\joinup\Traits\MailConfigTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;
use weitzman\DrupalTestTraits\Mail\MailCollectionTrait;

/**
 * Base class for Joinup ExistingSite tests.
 */
abstract class JoinupExistingSiteTestBase extends ExistingSiteBase {

  use AntibotTrait;
  use MailCollectionTrait {
    startMailCollection as traitStartMailCollection;
    restoreMailSettings as traitRestoreMailSettings;
  }
  use MailConfigTrait;

  /**
   * The list of Honeypot forms.
   *
   * @var bool[]
   */
  protected static $honeypotForms;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the testing mail collector during tests.
    $this->startMailCollection();

    // As ExistingSiteBase tests are running without javascript, we disable
    // Antibot and Honeypot functionality during the tests run.
    $this->disableAntibot();
    $this->disableHoneypot();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Restore Antibot and Honeypot functionality after test run.
    $this->restoreAntibot();
    $this->restoreHoneypot();

    // Make sure we don't send any notifications during test entities cleanup.
    foreach ($this->cleanupEntities as $entity) {
      $entity->skip_notification = TRUE;
    }

    // Restore the mail settings.
    $this->restoreMailSettings();

    parent::tearDown();

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $delete_orphans_manager */
    $delete_orphans_manager = \Drupal::service('plugin.manager.og.delete_orphans');
    /** @var \Drupal\og\OgDeleteOrphansInterface $delete_orphans_plugin */
    $delete_orphans_plugin = $delete_orphans_manager->createInstance('simple');
    // Delete the OG group content orphans now because parent::tearDown() is
    // destroying the container and the registered shutdown callback will fail.
    $delete_orphans_plugin->process();
  }

  /**
   * Overrides the trait method by bypassing config read-only.
   *
   * @throws \Exception
   *   If mail config is overwritten in settings.php or settings.local.php.
   */
  protected function startMailCollection(): void {
    // Check if the mail system configuration has been overridden in
    // settings.php or settings.local.php.
    $this->checkMailConfigOverride();

    static::bypassReadOnlyConfig();
    $this->traitStartMailCollection();
    static::restoreReadOnlyConfig();

    $this->resetTestMailCollector();
  }

  /**
   * Overrides the trait method by bypassing config read-only.
   */
  protected function restoreMailSettings(): void {
    static::bypassReadOnlyConfig();
    $this->traitRestoreMailSettings();
    static::restoreReadOnlyConfig();
  }

  /**
   * Disables Honeypot settings during the test run.
   */
  protected function disableHoneypot(): void {
    static::bypassReadOnlyConfig();
    $config_factory = \Drupal::configFactory();
    $config = $config_factory->getEditable('honeypot.settings');
    if (!isset($this->honeypotForms)) {
      static::$honeypotForms = $config->get('form_settings');
    }
    $config
      ->set('form_settings', array_fill_keys(array_keys(static::$honeypotForms), FALSE))
      ->save();
    static::restoreReadOnlyConfig();
  }

  /**
   * Restores Honeypot settings after test run.
   */
  protected function restoreHoneypot(): void {
    static::bypassReadOnlyConfig();
    \Drupal::configFactory()->getEditable('honeypot.settings')
      ->set('form_settings', static::$honeypotForms)
      ->save();
    static::restoreReadOnlyConfig();
  }

}

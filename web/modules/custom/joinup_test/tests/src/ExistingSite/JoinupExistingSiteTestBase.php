<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_test\ExistingSite;

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
  protected $honeypotForms;

  /**
   * Whether the current test should run without Antibot & Honeypot features.
   *
   * @var bool
   */
  protected $disableSpamProtection = TRUE;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use the testing mail collector during tests.
    $this->startMailCollection();

    // A user whose account in not yet linked with an EU Login account has
    // limited access to the website features. They can login only by using the
    // one-time-login mechanism, but one-time-login is meant only to allow
    // password recovery. On the other hand, in testing, we use the
    // one-time-login mechanism to perform the authentication, instead of
    // following the CAS login process, thus we disable limited access. Tests
    // that are specifically testing limited access are able to use this
    // kill-switch by temporary re-enabling the functionality during testing.
    // @see \Drupal\Tests\joinup_eulogin\ExistingSite\JoinupEuLoginTest::testLimitedAccess()
    $this->state = $this->container->get('state');
    $this->state->set('joinup_eulogin.disable_limited_access', TRUE);

    if ($this->disableSpamProtection) {
      // As ExistingSiteBase tests are running without javascript, we disable
      // Antibot & Honeypot during the tests run, if it has been requested.
      $this->disableAntibot();
      $this->disableHoneypot();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Restores the spam protection functionality, if case.
    if ($this->disableSpamProtection) {
      $this->restoreAntibot();
      $this->restoreHoneypot();
    }

    // Make sure we don't send any notifications during test entities cleanup.
    foreach ($this->cleanupEntities as $entity) {
      $entity->skip_notification = TRUE;
    }

    // Re-enable limited access functionality.
    $this->state->delete('joinup_eulogin.disable_limited_access');

    // Restore the mail settings.
    $this->restoreMailSettings();

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $delete_orphans_manager */
    $delete_orphans_manager = $this->container->get('plugin.manager.og.delete_orphans');
    /** @var \Drupal\og\OgDeleteOrphansInterface $delete_orphans_plugin */
    $delete_orphans_plugin = $delete_orphans_manager->createInstance('simple');

    // The parent method might cleanup config entities.
    $this->bypassReadOnlyConfig();
    parent::tearDown();
    $this->restoreReadOnlyConfig();

    // Delete the OG group content orphans now because parent::tearDown() is
    // destroying the container and the registered shutdown callback will fail.
    $delete_orphans_plugin->process();
  }

  /**
   * Overrides the trait method by bypassing config read-only.
   *
   * @throws \Exception
   *   If mail config is overwritten in settings.php or settings.override.php.
   */
  protected function startMailCollection(): void {
    // Check if the mail system configuration has been overridden in
    // settings.php or settings.override.php.
    static::checkMailConfigOverride();

    static::bypassReadOnlyConfig();
    static::traitStartMailCollection();
    static::restoreReadOnlyConfig();
  }

  /**
   * Overrides the trait method by bypassing config read-only.
   */
  protected function restoreMailSettings(): void {
    static::bypassReadOnlyConfig();
    static::traitRestoreMailSettings();
    static::restoreReadOnlyConfig();
  }

  /**
   * Disables Honeypot settings during the test run.
   */
  protected function disableHoneypot(): void {
    static::bypassReadOnlyConfig();
    $config_factory = $this->container->get('config.factory');
    $config = $config_factory->getEditable('honeypot.settings');
    if (!isset($this->honeypotForms)) {
      $this->honeypotForms = $config->get('form_settings') ?? [];
    }
    $config
      ->set('form_settings', array_fill_keys(array_keys($this->honeypotForms), FALSE))
      ->save();
    static::restoreReadOnlyConfig();
  }

  /**
   * Restores Honeypot settings after test run.
   */
  protected function restoreHoneypot(): void {
    static::bypassReadOnlyConfig();
    $this->container->get('config.factory')->getEditable('honeypot.settings')
      ->set('form_settings', $this->honeypotForms)
      ->save();
    static::restoreReadOnlyConfig();
  }

}

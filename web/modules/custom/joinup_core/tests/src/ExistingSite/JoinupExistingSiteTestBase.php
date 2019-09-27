<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for Joinup  ExistingSite tests.
 */
class JoinupExistingSiteTestBase extends ExistingSiteBase {

  use ConfigReadOnlyTrait;

  /**
   * The current list of form IDs protected by Antibot.
   *
   * @var string[]
   */
  protected $currentAntibotFormIds;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $antibot_settings = \Drupal::configFactory()->getEditable('antibot.settings');
    // Save the current form IDs protected by Antibot.
    $this->currentAntibotFormIds = $antibot_settings->get('form_ids');
    // Unprotect all forms for the scope of this test. Antibot is blocking all
    // form submissions when javascript is disabled. As ExistingSiteBase tests
    // are running without javascript, we disable Antibot for all forms during
    // the tests.
    $this->bypassReadOnlyConfig();
    $antibot_settings->set('form_ids', [])->save();
    $this->restoreReadOnlyConfig();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Make sure we don't send any notifications during test entities cleanup.
    foreach ($this->cleanupEntities as $entity) {
      $entity->skip_notification = TRUE;
    }

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $delete_orphans_manager */
    $delete_orphans_manager = \Drupal::service('plugin.manager.og.delete_orphans');
    /** @var \Drupal\og\OgDeleteOrphansInterface $delete_orphans_plugin */
    $delete_orphans_plugin = $delete_orphans_manager->createInstance('simple');
    // Delete the OG group content orphans now because parent::tearDown() is
    // destroying the container and the registered shutdown callback will fail.
    $delete_orphans_plugin->process();

    // Restore the list of form IDs to be protected by Antibot.
    $this->bypassReadOnlyConfig();
    \Drupal::configFactory()
      ->getEditable('antibot.settings')
      ->set('form_ids', $this->currentAntibotFormIds)
      ->save();
    $this->restoreReadOnlyConfig();

    parent::tearDown();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\joinup\Traits\AntibotTrait;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for Joinup ExistingSite tests.
 */
class JoinupExistingSiteTestBase extends ExistingSiteBase {

  use AntibotTrait;

  /**
   * Whether the current test should run with Antibot features disabled.
   *
   * @var bool
   */
  protected $disableAntibot = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if ($this->disableAntibot) {
      // As ExistingSiteBase tests are running without javascript, we disable
      // Antibot during the tests run, if it has been requested.
      static::disableAntibot();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Make sure we don't send any notifications during test entities cleanup.
    foreach ($this->cleanupEntities as $entity) {
      $entity->skip_notification = TRUE;
    }

    parent::tearDown();

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $delete_orphans_manager */
    $delete_orphans_manager = \Drupal::service('plugin.manager.og.delete_orphans');
    /** @var \Drupal\og\OgDeleteOrphansInterface $delete_orphans_plugin */
    $delete_orphans_plugin = $delete_orphans_manager->createInstance('simple');
    // Delete the OG group content orphans now because parent::tearDown() is
    // destroying the container and the registered shutdown callback will fail.
    $delete_orphans_plugin->process();

    // Restores the Antibot functionality, if case.
    if ($this->disableAntibot) {
      static::restoreAntibot();
    }
  }

}

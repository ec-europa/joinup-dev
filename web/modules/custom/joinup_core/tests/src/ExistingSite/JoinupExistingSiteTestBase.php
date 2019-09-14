<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for Joinup  ExistingSite tests.
 */
class JoinupExistingSiteTestBase extends ExistingSiteBase {

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

    parent::tearDown();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Core\Extension\Extension;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests that the config of our custom modules is listed in their info files.
 *
 * @group joinup_core
 */
class ConfigDevelExportedTest extends JoinupExistingSiteTestBase {

  use DrushTestTrait;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->container->get('module_handler');
  }

  /**
   * Tests that the list of exported config is correct.
   */
  public function testConfigDevelExportedConfig(): void {
    // Get a list of all enabled custom modules.
    /** @var \Drupal\Core\Extension\Extension[] $modules */
    $modules = array_filter($this->moduleHandler->getModuleList(), function (Extension $extension) {
      return substr($extension->getPath(), 0, 14) === 'modules/custom';
    });

    $extension_names = array_map(function (Extension $extension) {
      return $extension->getName();
    }, $modules);

    // Also check the profile and the theme.
    $extension_names += ['joinup', 'joinup_theme'];

    foreach ($extension_names as $extension_name) {
      $this->drush('config:devel-update-info', ['extension' => $extension_name], ['check' => NULL]);
    }

    // The test will fail if the above Drush command throws an exception when a
    // module has invalid exported config. We need to do one assertion though or
    // PHPUnit will mark this test as risky.
    $this->assertTrue(TRUE);
  }

}

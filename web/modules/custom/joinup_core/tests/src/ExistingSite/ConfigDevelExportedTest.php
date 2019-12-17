<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drush\TestTraits\DrushTestTrait;

/**
 * Tests that the config of all enabled modules is listed in their info files.
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
   * Tests drush commands.
   */
  public function testCommands() {
    $this->drush('config:devel-update-info', ['extension' => 'joinup_core'], ['check' => NULL]);
  }

}

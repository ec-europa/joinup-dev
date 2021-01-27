<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\SchemaCheckTestTrait;
use Drupal\config_test\TestInstallStorage;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests Joinup sync configuration.
 *
 * @group joinup_core
 */
class JoinupConfigSyncTest extends KernelTestBase {

  use SchemaCheckTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // This module alters the schema of facet.widget.default_config.
    // @see search_api_arbitrary_facet_config_schema_info_alter()
    'search_api_arbitrary_facet',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Use a testing schema storage service that records all schemas from all
    // extensions, regardless if they are installed or not.
    $container->register('joinup_config_test.schema_storage')
      ->setClass(TestInstallStorage::class)
      ->addArgument(InstallStorage::CONFIG_SCHEMA_DIRECTORY);
    $container->getDefinition('config.typed')
      ->replaceArgument(1, new Reference('joinup_config_test.schema_storage'));
  }

  /**
   * Tests that config/sync configurations adhere to their schema.
   */
  public function testDefaultConfig(): void {
    $typed_config = $this->container->get('config.typed');
    $config_sync_dir = realpath("{$this->root}/../config/sync");
    $config_sync_storage = new FileStorage($config_sync_dir);
    foreach ($config_sync_storage->listAll() as $config_name) {
      $config_data = $config_sync_storage->read($config_name);
      $this->assertConfigSchema($typed_config, $config_name, $config_data);
    }
  }

}

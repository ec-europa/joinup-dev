<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Provides an abstract class for most of the SPARQL kernel tests.
 */
class SparqlKernelTestBase extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'sparql_entity_storage',
    'sparql_test',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function bootEnvironment() {
    parent::bootEnvironment();
    $this->setUpSparql();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['sparql_entity_storage', 'sparql_test']);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Delete 'sparql_test' entities that might have been created during tests.
    $storage = $this->container->get('entity_type.manager')->getStorage('sparql_test');
    $ids = $storage->getQuery()->execute();
    $storage->delete($storage->loadMultiple($ids));
    parent::tearDown();
  }

}

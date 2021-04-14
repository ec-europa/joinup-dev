<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests the UUID computed field.
 *
 * @group rdf_entity
 */
class RdfEntityUuid extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_entity',
    'sparql_entity_serializer_test',
    'sparql_entity_storage',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();
    $this->installConfig([
      'rdf_entity',
      'sparql_entity_serializer_test',
      'sparql_entity_storage',
    ]);
  }

  /**
   * Tests the UUID computed field.
   */
  public function testUuid(): void {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');

    Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/apple',
      'label' => 'Apple',
    ])->save();

    $entities = $storage->loadByProperties(['uuid' => 'http://example.com/apple']);
    $this->assertEquals('http://example.com/apple', $entities['http://example.com/apple']->uuid());
    $this->assertEquals('http://example.com/apple', $entities['http://example.com/apple']->get('uuid')->value);

    $entities = Rdf::loadMultiple(['http://example.com/apple']);
    $this->assertEquals('http://example.com/apple', $entities['http://example.com/apple']->uuid());
    $this->assertEquals('http://example.com/apple', $entities['http://example.com/apple']->get('uuid')->value);

    $entities = $storage->loadMultiple(['http://example.com/apple']);
    $this->assertEquals('http://example.com/apple', $entities['http://example.com/apple']->uuid());
    $this->assertEquals('http://example.com/apple', $entities['http://example.com/apple']->get('uuid')->value);

    $apple = Rdf::load('http://example.com/apple');
    $this->assertEquals('http://example.com/apple', $apple->uuid());
    $this->assertEquals('http://example.com/apple', $apple->get('uuid')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    Rdf::load('http://example.com/apple')->delete();
    parent::tearDown();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\sparql_serialization_test\Entity\SimpleSparqlTest;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the SPARQL serializer.
 *
 * @group sparql_entity_storage
 */
class SparqlSerializerTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'sparql_entity_storage',
    'sparql_serialization_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function bootEnvironment(): void {
    parent::bootEnvironment();
    $this->setUpSparql();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig([
      'sparql_entity_storage',
      'sparql_serialization_test',
    ]);
  }

  /**
   * Tests content negotiation.
   */
  public function testContentNegotiation(): void {
    $entity = SimpleSparqlTest::create([
      'type' => 'fruit',
      'id' => 'http://example.com/apple',
      'title' => 'Apple',
    ]);
    $entity->save();

    $encoders = $this->container->getParameter('sparql_entity.encoders');
    $serializer = $this->container->get('sparql_entity.serializer');
    foreach ($encoders as $format => $content_type) {
      $serialized = trim($serializer->serializeEntity($entity, $format));
      $expected = trim(file_get_contents(__DIR__ . "/../../fixtures/content-negotiation/rdf_entity/$format"));
      $this->assertEquals($expected, $serialized);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    SimpleSparqlTest::load('http://example.com/apple')->delete();
    parent::tearDown();
  }

}

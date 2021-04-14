<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\sparql_entity_storage\Entity\SparqlGraph;
use Drupal\sparql_entity_storage\Exception\DuplicatedIdException;
use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests the creation of entities based on SPARQL entity storage.
 *
 * @coversDefaultClass \Drupal\sparql_entity_storage\SparqlEntityStorage
 *
 * @group sparql_entity_storage
 */
class EntityCreationTest extends SparqlKernelTestBase {

  /**
   * Tests overlapping IDs.
   *
   * @covers ::doSave
   */
  public function testOverlappingIds(): void {
    // Create a sparql_test entity.
    SparqlTest::create([
      'type' => 'fruit',
      'id' => 'http://example.com/apple',
      'title' => 'Apple',
    ])->save();

    // Check that on saving an existing entity no exception is thrown.
    SparqlTest::load('http://example.com/apple')->save();

    // Check that new test entity, with its own ID, don't raise any exception.
    SparqlTest::create([
      'type' => 'fruit',
      'id' => 'http://example.com/berry',
      'title' => 'Fruit with a different ID',
    ])->save();

    // Check that the expected exception is throw when trying to create a new
    // entity with the same ID.
    $this->expectException(DuplicatedIdException::class);
    $this->expectExceptionMessage("Attempting to create a new entity with the ID 'http://example.com/apple' already taken.");
    SparqlTest::create([
      'type' => 'fruit',
      'id' => 'http://example.com/apple',
      'title' => "This fruit tries to steal the Apple's ID",
    ])->save();
  }

  /**
   * Tests that the default graph is set on entity creation.
   *
   * @covers ::create
   */
  public function testDefaultGraphSetOnCreate(): void {
    // Check that the default graph is set when no graph is specified.
    $entity = SparqlTest::create([
      'type' => 'waffle',
      'id' => 'http://example.com/kempense-galet',
      'title' => 'Kempense galet',
    ]);

    $this->assertEquals(SparqlGraph::DEFAULT, $entity->get('graph')->target_id);

    // Check that it is possible to specify a custom graph.
    $entity = SparqlTest::create([
      'type' => 'waffle',
      'id' => 'http://example.com/liege-waffle',
      'title' => 'Liège waffle',
      'graph' => 'custom_graph',
    ]);

    $this->assertEquals('custom_graph', $entity->get('graph')->target_id);
  }

}

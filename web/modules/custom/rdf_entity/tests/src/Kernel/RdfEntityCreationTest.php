<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Exception\DuplicatedIdException;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Provides unit testing for the 'rdf_entity' entity.
 *
 * @coversDefaultClass \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage
 *
 * @group rdf_entity
 */
class RdfEntityCreationTest extends JoinupKernelTestBase {

  /**
   * Tests overlapping IDs.
   *
   * @covers ::doSave
   */
  public function testOverlappingIds() {
    // Create a rdf_entity.
    Rdf::create([
      'rid' => 'dummy',
      'id' => 'http://example.com',
      'label' => 'Foo',
    ])->save();

    // Check that on saving an existing entity no exception is thrown.
    Rdf::load('http://example.com')->save();

    // Check that new rdf_entity, with its own ID, don't raise any exception.
    Rdf::create([
      'rid' => 'dummy',
      'id' => 'http://example.com/different-id',
      'label' => 'Entity with a different ID',
    ])->save();

    // Check that the expected exception is throw when trying to create a new
    // entity with the same ID.
    $this->setExpectedException(DuplicatedIdException::class, "Attempting to create a new entity with the ID 'http://example.com' already taken.");
    Rdf::create([
      'rid' => 'dummy',
      'id' => 'http://example.com',
      'label' => "This entity tries to steal the first entity's ID",
    ])->save();
  }

}

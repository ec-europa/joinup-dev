<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\Component\Utility\Crypt;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Tests RDF entity.
 *
 * @group rdf_entity
 */
class RdfEntityTest extends JoinupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rdf_entity_test',
    'field',
  ];

  /**
   * Tests RDF entity CRUD.
   */
  public function testMain() {
    $this->installConfig(['rdf_entity']);
    $this->installEntitySchema('rdf_entity');
    $this->installConfig(['rdf_entity_test']);

    // Create a rdf_entity with a pre-filled URI.
    $rdf = Rdf::create([
      'uri' => 'http://example.com',
      'rid' => 'dummy',
      'label' => 'Foo',
      'field_text' => 'Bar',
    ]);
    $rdf->save();

    // Check that values were retrieved after loading from triple store.
    $rdf = Rdf::load($rdf->id());

//print_r($rdf->toArray());
    $this->assertEquals('http://example.com', $rdf->getUri());
    $this->assertEquals('http://example.com', $rdf->uuid());
    $this->assertEquals(Crypt::hashBase64('http://example.com'), $rdf->id());
    $this->assertEquals('dummy', $rdf->bundle());
    $this->assertEquals('Foo', $rdf->label());
    $this->assertEquals('Bar', $rdf->field_text->value);

    // Create a rdf_entity with no initial URI.
//    $rdf = Rdf::create([
//      'rid' => 'dummy',
//      'label' => 'Baz',
//      'field_text' => 'Qux',
//    ]);
//    $rdf->save();
//
//    // Check that values were retrieved after loading from triple store.
//    $rdf = Rdf::load($rdf->id());
//    $this->assertEquals($rdf->getUri(), $rdf->uuid());
//    $this->assertEquals(Crypt::hashBase64($rdf->getUri()), $rdf->id());
//    $this->assertEquals('dummy', $rdf->bundle());
//    $this->assertEquals('Baz', $rdf->label());
//    $this->assertEquals('Qux', $rdf->field_text->value);
  }

}

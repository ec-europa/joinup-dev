<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests for the RDF entity class.
 *
 * @coversDefaultClass \Drupal\rdf_entity\Entity\Rdf
 */
class RdfEntityTest extends RdfKernelTestBase {

  /**
   * @covers \Drupal\rdf_entity\Entity\Rdf::hasGraph
   */
  public function testHasGraph() {
    $rdf_entity = Rdf::create([
      'rid' => 'dummy',
      'label' => $this->randomMachineName(),
    ]);
    $this->assertFalse($rdf_entity->hasGraph('default'));

    $rdf_entity->save();
    $this->assertTrue($rdf_entity->hasGraph('default'));
    $this->assertFalse($rdf_entity->hasGraph('draft'));

    $rdf_entity->set('graph', 'draft')->save();
    $this->assertTrue($rdf_entity->hasGraph('default'));
    $this->assertTrue($rdf_entity->hasGraph('draft'));
    $rdf_entity->deleteFromGraph('default');
    $this->assertFalse($rdf_entity->hasGraph('default'));
    $this->assertTrue($rdf_entity->hasGraph('draft'));
  }

}

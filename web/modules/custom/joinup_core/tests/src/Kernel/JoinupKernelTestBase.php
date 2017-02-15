<?php

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\rdf_entity\Tests\RdfDatabaseConnectionTrait;
use Drupal\rdf_entity\Tests\RdfKernelTestBase;

/**
 * Provides a base class for Joinup kernel tests.
 *
 * Mainly, assures the connection to the triple store database.
 *
 * IMPORTANT! You should not use real RDF entity bundles for testing because the
 * test is using the same backend storage as the main site and you can end up
 * with changes to the main site content. Create your own RDF entity bundles for
 * testing purposes, like the one provided in the rdf_entity_test.module. That
 * module uses a dedicated testing graphs, (http://example.com/dummy/published
 * and http://example.com/dummy/draft). This base class enables, at startup, the
 * rdf_entity_test.module and takes care of deleting testing data. For other
 * custom testing data that you are adding for testing, you should take care of
 * cleaning it after the test. You can extend the tearDown() method for this
 * purpose.
 */
abstract class JoinupKernelTestBase extends RdfKernelTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rdf_entity',
    'field',
    'system',
    'user',
    'rdf_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    if (!$this->setUpSparql()) {
      $this->markTestSkipped('No Sparql connection available.');
    }
    // Test is not compatible with Virtuoso 6.
    if ($this->detectVirtuoso6()) {
      $this->markTestSkipped('Skipping: Not running on Virtuoso 6.');
    }
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installConfig(['rdf_entity']);
    $this->installEntitySchema('rdf_entity');
    $this->installConfig(['rdf_entity_test']);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete all data produced by testing module.
    foreach (['published', 'draft'] as $graph) {
      $query = <<<EndOfQuery
DELETE {
  GRAPH <http://example.com/dummy/$graph> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <http://example.com/dummy/$graph> {
    ?entity ?field ?value
  }
}
EndOfQuery;
      $this->sparql->query($query);
    }

    parent::tearDown();
  }

}

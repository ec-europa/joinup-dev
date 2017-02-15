<?php

namespace Drupal\tests\rdf_entity;

use Drupal\Tests\BrowserTestBase;

/**
 * Base setup for a Joinup workflow test.
 */
abstract class RdfWebTestBase extends BrowserTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * An array of graphs to clear after the test.
   *
   * @var array
   */
  protected $usedGraphs = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // The SPARQL connection has to be set up before.
    if (!$this->setUpSparql()) {
      $this->markTestSkipped('No Sparql connection available.');
    }
    // Test is not compatible with Virtuoso 6.
    if ($this->detectVirtuoso6()) {
      $this->markTestSkipped('Skipping: Not running on Virtuoso 6.');
    }
    $this->setUpSparqlForBrowser();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete all data produced by testing module.
    foreach ($this->usedGraphs as $graph) {
      $query = <<<EndOfQuery
DELETE {
  GRAPH <$graph> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <$graph> {
    ?entity ?field ?value
  }
}
EndOfQuery;
      $this->sparql->query($query);
    }

    parent::tearDown();
  }

}

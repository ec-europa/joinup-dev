<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use EasyRdf\Graph;

/**
 * Tests the 'remove_unsupported_data' process step plugin.
 *
 * @group joinup_federation
 */
class RemoveUnsupportedDataStepTest extends StepTestBase {

  /**
   * Test ADMSv2 changes.
   */
  public function test() {
    $graph = new Graph(static::getTestingGraphs()['sink']);
    $graph->parseFile(__DIR__ . '/../../fixtures/valid_adms.rdf');
    $this->createGraphStore()->replace($graph);

    $result = $this->runPipelineStep('remove_unsupported_data');

    // Check that the step ran without any error.
    $this->assertNull($result);

    $query = <<<Query
SELECT ?graph ?subject ?predicate ?object
FROM NAMED <{$this->getTestingGraphs()['sink']}>
WHERE {
  GRAPH ?graph { ?subject ?predicate ?object }
}
Query;

    $unsupported_triples_count = 0;
    foreach ($this->sparql->query($query) as $triple) {
      // Only http://vocabulary/term triples are unsupported in the test file.
      if ($triple->subject->getUri() == 'http://vocabulary/term') {
        $unsupported_triples_count++;
      }
    }

    // Check that all remaining triples are supported.
    $this->assertEquals(0, $unsupported_triples_count);
  }

}

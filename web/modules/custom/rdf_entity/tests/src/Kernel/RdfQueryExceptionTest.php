<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\rdf_entity\Exception\SparqlQueryException;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Test that a proper exception is thrown when a query fails.
 *
 * @group rdf_entity
 */
class RdfQueryExceptionTest extends JoinupKernelTestBase {

  /**
   * Test that the exception message contains the query itself (debugging).
   */
  public function testQueryException() {
    /** @var \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql */
    $sparql = $this->container->get('sparql_endpoint');
    $this->setExpectedException(SparqlQueryException::class, "Execution of query failed: SELECT ?o WHERE { ?s ?p }");
    $sparql->query('SELECT ?o WHERE { ?s ?p }');
  }

}

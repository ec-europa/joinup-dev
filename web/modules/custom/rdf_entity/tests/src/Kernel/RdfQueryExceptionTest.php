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
   * Exception with query in message thrown for selects.
   */
  public function testQuerySelectException() {
    /** @var \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql */
    $sparql = $this->container->get('sparql_endpoint');
    $this->setExpectedException(SparqlQueryException::class, "Execution of query failed: SELECT ?o WHERE { ?s ?p }");
    $sparql->query('SELECT ?o WHERE { ?s ?p }');
  }

  /**
   * Exception with query in message thrown for updates.
   */
  public function testQueryUpdateException() {
    /** @var \Drupal\rdf_entity\Database\Driver\sparql\Connection $sparql */
    $sparql = $this->container->get('sparql_endpoint');
    $this->setExpectedException(SparqlQueryException::class, "Execution of query failed: INSERT DATA INTO <\malformed> {}");
    $sparql->update('INSERT DATA INTO <\malformed> {}');
  }

}

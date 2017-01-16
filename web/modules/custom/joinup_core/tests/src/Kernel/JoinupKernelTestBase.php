<?php

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use EasyRdf\Http;

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
abstract class JoinupKernelTestBase extends KernelTestBase {

  /**
   * The SPARQL database info.
   *
   * @var array
   */
  protected $sparqlConnectionInfo;

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rdf_entity',
    'taxonomy',
    'field',
    'system',
    'user',
    'rdf_entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    if (!$this->setUpSparql()) {
      $this->markTestSkipped('No Sparql connection available.');
    }
    // Test is not compatible with Virtuoso 6.
    if ($this->detectVirtuoso6()) {
      $this->markTestSkipped('Skipping: Not running on Virtuoso 6.');
    }

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

  /**
   * Checks if the triple store is an Virtuoso 6 instance.
   *
   * @return bool
   *   TRUE if it's a Virtuoso 6 server.
   */
  protected function detectVirtuoso6() {
    $client = Http::getDefaultHttpClient();
    $client->resetParameters(TRUE);
    $client->setUri("http://{$this->sparqlConnectionInfo['host']}:{$this->sparqlConnectionInfo['port']}/");
    $client->setMethod('GET');
    $response = $client->request();
    $server_header = $response->getHeader('Server');
    if (strpos($server_header, "Virtuoso/06") === FALSE) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Setup the db connection to the triple store.
   */
  protected function setUpSparql() {
    // If the test is run with argument db url then use it.
    // export SIMPLETEST_SPARQL_DB='sparql://127.0.0.1:8890/'.
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    if (empty($db_url)) {
      return FALSE;
    }
    $this->sparqlConnectionInfo = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
    $this->sparqlConnectionInfo['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';
    Database::addConnectionInfo('sparql_default', 'default', $this->sparqlConnectionInfo);

    $this->sparql = Database::getConnection('default', 'sparql_default');

    return TRUE;
  }

}

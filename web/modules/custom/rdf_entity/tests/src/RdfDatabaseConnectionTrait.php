<?php

namespace Drupal\Tests\rdf_entity;

use Drupal\Core\Database\Database;
use EasyRdf\Http;

/**
 * Provides helpers to add a SPARQL database connection in tests.
 */
trait RdfDatabaseConnectionTrait {

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Connection
   */
  protected $sparql;

  /**
   * The SPARQL database info.
   *
   * @var array
   */
  protected $sparqlConnectionInfo;

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
   *
   * @return bool
   *   TRUE if the connection has been set correctly.
   */
  protected function setUpSparql() {
    // If the test is run with argument db url then use it.
    // export SIMPLETEST_SPARQL_DB='sparql://127.0.0.1:8890/'.
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    if (empty($db_url)) {
      return FALSE;
    }

    $this->sparqlConnectionInfo = Database::convertDbUrlToConnectionInfo($db_url, dirname(dirname(__FILE__)));
    $this->sparqlConnectionInfo['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';
    Database::addConnectionInfo('sparql_default', 'default', $this->sparqlConnectionInfo);

    $this->sparql = Database::getConnection('default', 'sparql_default');

    return TRUE;
  }

}

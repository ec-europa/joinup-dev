<?php

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use EasyRdf\Http;

/**
 * Provides a base class for Joinup kernel tests.
 *
 * Mainly, assures the connection to the triple store database.
 */
abstract class JoinupKernelTestBase extends KernelTestBase {

  /**
   * The SPARQL database connection.
   *
   * @var array
   */
  protected $sparqlDatabase;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'rdf_entity',
    'system',
    'user',
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
  }

  /**
   * Checks if the triple store is an Virtuoso 6 instance.
   */
  protected function detectVirtuoso6() {
    $client = Http::getDefaultHttpClient();
    $client->resetParameters(TRUE);
    $client->setUri("http://{$this->sparqlDatabase['host']}:{$this->sparqlDatabase['port']}/");
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
    $this->sparqlDatabase = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
    $this->sparqlDatabase['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';
    Database::addConnectionInfo('sparql_default', 'default', $this->sparqlDatabase);

    return TRUE;
  }

}

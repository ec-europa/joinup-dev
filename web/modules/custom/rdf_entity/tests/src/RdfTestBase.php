<?php

namespace Drupal\Tests\rdf_entity;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use EasyRdf\Http;

/**
 * A base class for the rdf tests.
 *
 * Sets up the SPARQL database connection.
 */
class RdfTestBase extends EntityKernelTestBase {

  /**
   * The SPARQL database connection.
   *
   * @var array
   */
  protected $database;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array(
    'ds',
    'comment',
    'field',
    'system',
  );

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

    $this->installModule('rdf_entity');
    $this->installModule('rdf_draft');
    $this->installConfig(['rdf_entity', 'rdf_draft']);
    $this->installEntitySchema('rdf_entity');
  }

  /**
   * Checks if the triple store is an Virtuoso 6 instance.
   */
  protected function detectVirtuoso6() {
    $client = Http::getDefaultHttpClient();
    $client->resetParameters(TRUE);
    $client->setUri("http://{$this->database['host']}:{$this->database['port']}/");
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
    $this->database = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
    $this->database['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';
    Database::addConnectionInfo('sparql_default', 'default', $this->database);

    return TRUE;
  }

  /**
   * Clear the index after every test.
   */
  public function tearDown() {
    parent::tearDown();
  }

}

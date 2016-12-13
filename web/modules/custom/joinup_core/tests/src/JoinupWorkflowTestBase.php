<?php

namespace Drupal\Tests\joinup_core;

use Drupal\Core\Database\Database;
use Drupal\Tests\BrowserTestBase;
use EasyRdf\Http;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group rdf_entity
 */
class JoinupWorkflowTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'joinup';

  /**
   * The og membership access manager service.
   *
   * @var \Drupal\og\OgAccess
   */
  protected $ogAccess;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->ogAccess = $this->container->get('og.access');
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

<?php

namespace Drupal\Tests\rdf_entity\Traits;

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
   * @throws \Exception
   *   When Virtuoso version is 6.
   */
  protected function detectVirtuoso6() {
    $client = Http::getDefaultHttpClient();
    $client->resetParameters(TRUE);
    $client->setUri("http://{$this->sparqlConnectionInfo['host']}:{$this->sparqlConnectionInfo['port']}/");
    $client->setMethod('GET');
    $response = $client->request();
    $server_header = $response->getHeader('Server');
    if (strpos($server_header, "Virtuoso/06") !== FALSE) {
      throw new \Exception('Not running on Virtuoso 6.');
    }
  }

  /**
   * Setup the db connection to the triple store.
   *
   * @throws \LogicException
   *   When SIMPLETEST_SPARQL_DB is not set.
   */
  protected function setUpSparql() {
    // If the test is run with argument db url then use it.
    // export SIMPLETEST_SPARQL_DB='sparql://127.0.0.1:8890/'.
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    if (empty($db_url)) {
      throw new \LogicException('No Sparql connection was defined. Set the SIMPLETEST_SPARQL_DB environment variable.');
    }

    $this->sparqlConnectionInfo = Database::convertDbUrlToConnectionInfo($db_url, dirname(dirname(__FILE__)));
    $this->sparqlConnectionInfo['namespace'] = 'Drupal\\rdf_entity\\Database\\Driver\\sparql';

    // Do not allow Virtuoso 6.
    $this->detectVirtuoso6();

    Database::addConnectionInfo('sparql_default', 'default', $this->sparqlConnectionInfo);

    $this->sparql = Database::getConnection('default', 'sparql_default');
  }

  /**
   * Sets the connection details to the settings.php file.
   *
   * The BrowserTestBase is creating a new copy of the settings.php file to the
   * test directory so the sparql entry needs to be inserted to the new
   * configuration.
   */
  protected function setUpSparqlForBrowser() {
    $key = 'sparql_default';
    $target = 'default';

    $settings['databases'][$key][$target] = (object) [
      'value' => Database::getConnectionInfo($key)[$target],
      'required' => TRUE,
    ];

    $settings_file = \Drupal::service('site.path') . '/settings.php';

    // Settings file is readonly at the moment.
    chmod($settings_file, 0666);
    drupal_rewrite_settings($settings);
    // Restore original permissions to the settings file.
    chmod($settings_file, 0444);
  }

}

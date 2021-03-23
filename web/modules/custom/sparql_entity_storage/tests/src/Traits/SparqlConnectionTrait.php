<?php

namespace Drupal\Tests\sparql_entity_storage\Traits;

use Drupal\Core\Database\Database;
use DrupalFinder\DrupalFinder;

/**
 * Provides helpers to add a SPARQL database connection in tests.
 */
trait SparqlConnectionTrait {

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface
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

  /**
   * Configures the DB connection to the triple store.
   *
   * @throws \LogicException
   *   When SIMPLETEST_SPARQL_DB is not set.
   */
  protected function setUpSparql() {
    $db_url = getenv('SIMPLETEST_SPARQL_DB');
    if (empty($db_url)) {
      throw new \LogicException('No Sparql connection was defined. Set the SIMPLETEST_SPARQL_DB environment variable.');
    }

    if (!defined('DRUPAL_ROOT')) {
      $drupalFinder = new DrupalFinder();
      $drupalFinder->locateRoot(__DIR__);
      $root = $drupalFinder->getDrupalRoot();
      require_once "$root/core/includes/bootstrap.inc";
    }

    $this->sparqlConnectionInfo = Database::convertDbUrlToConnectionInfo($db_url, DRUPAL_ROOT);
    $this->sparqlConnectionInfo['namespace'] = 'Drupal\\sparql_entity_storage\\Driver\\Database\\sparql';
    Database::addConnectionInfo('sparql_default', 'default', $this->sparqlConnectionInfo);

    $this->sparql = Database::getConnection('default', 'sparql_default');
  }

  /**
   * {@inheritdoc}
   */
  protected function writeSettings(array $settings) {
    // The BrowserTestBase is creating a new copy of the settings.php file to
    // the test directory so the SPARQL entry needs to be inserted into the new
    // configuration.
    $key = 'sparql_default';
    $target = 'default';

    $settings['databases'][$key][$target] = (object) [
      'value' => Database::getConnectionInfo($key)[$target],
      'required' => TRUE,
    ];

    parent::writeSettings($settings);
  }

}

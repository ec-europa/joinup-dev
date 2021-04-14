<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Driver\Database\sparql;

use Drupal\Core\Database\Log;
use Drupal\sparql_entity_storage\Exception\SparqlQueryException;
use EasyRdf\Graph;
use EasyRdf\Http\Exception as EasyRdfException;
use EasyRdf\Sparql\Client;
use EasyRdf\Sparql\Result;

/**
 * @addtogroup database
 * @{
 */

/**
 * SPARQL connection service.
 */
class Connection implements ConnectionInterface {

  /**
   * The client instance object that performs requests to the SPARQL endpoint.
   *
   * @var \EasyRdf\Sparql\Client
   */
  protected $easyRdfClient;

  /**
   * The connection information for this connection object.
   *
   * @var array
   */
  protected $connectionOptions;

  /**
   * The static cache of a DB statement stub object.
   *
   * @var \Drupal\Core\Database\StatementInterface
   */
  protected $statementStub;

  /**
   * The database target this connection is for.
   *
   * We need this information for later auditing and logging.
   *
   * @var string|null
   */
  protected $target = NULL;

  /**
   * The key representing this connection.
   *
   * The key is a unique string which identifies a database connection. A
   * connection can be a single server or a cluster of primary and replicas
   * (use target to pick between primary and replica).
   *
   * @var string|null
   */
  protected $key = NULL;

  /**
   * The current database logging object for this connection.
   *
   * @var \Drupal\Core\Database\Log|null
   */
  protected $logger = NULL;

  /**
   * Constructs a new connection instance.
   *
   * @param \EasyRdf\Sparql\Client $easy_rdf_client
   *   Object of \EasyRdf\Sparql\Client which is a database connection.
   * @param array $connection_options
   *   An associative array of connection options. See the "Database settings"
   *   section from 'sites/default/settings.php' a for a detailed description of
   *   the structure of this array.
   */
  public function __construct(Client $easy_rdf_client, array $connection_options) {
    $this->easyRdfClient = $easy_rdf_client;
    $this->connectionOptions = $connection_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSparqlClient(): Client {
    return $this->easyRdfClient;
  }

  /**
   * {@inheritdoc}
   */
  public function query(string $query, array $args = [], array $options = []): Result {
    return $this->doQuery($query, $args, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function constructQuery(string $query, array $args = [], array $options = []): Graph {
    return $this->doQuery($query, $args, $options);
  }

  /**
   * Execute the query against the endpoint.
   *
   * @param string $query
   *   The string query to execute.
   * @param array $args
   *   An array of arguments for the query.
   * @param array $options
   *   An associative array of options to control how the query is run.
   *
   * @return \EasyRdf\Sparql\Result|\EasyRdf\Graph
   *   The query result.
   *
   * @throws \InvalidArgumentException
   *   If $args value is passed but arguments replacement is not yet
   *   supported. To be removed in #55.
   * @throws \Drupal\sparql_entity_storage\Exception\SparqlQueryException
   *   Exception during query execution, e.g. timeout.
   *
   * @see https://github.com/ec-europa/sparql_entity_storage/issues/1
   */
  protected function doQuery(string $query, array $args = [], array $options = []) {
    // @todo Remove this in #1.
    // @see https://github.com/ec-europa/sparql_entity_storage/issues/1
    if ($args) {
      throw new \InvalidArgumentException('Replacement arguments are not yet supported.');
    }

    if ($this->logger) {
      $query_start = microtime(TRUE);
    }

    try {
      // @todo Implement argument replacement in #1.
      // @see https://github.com/ec-europa/sparql_entity_storage/issues/1
      $results = $this->easyRdfClient->query($query);
    }
    catch (EasyRdfException $exception) {
      // Re-throw the exception, but with the query as message.
      throw new SparqlQueryException('Execution of query failed: ' . $query);
    }

    if ($this->logger) {
      $query_end = microtime(TRUE);
      $this->log($query, $args, $query_end - $query_start);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function update(string $query, array $args = [], array $options = []): Result {
    // @todo Remove this in #1.
    // @see https://github.com/ec-europa/sparql_entity_storage/issues/1
    if ($args) {
      throw new \InvalidArgumentException('Replacement arguments are not yet supported.');
    }

    if ($this->logger) {
      $query_start = microtime(TRUE);
    }

    try {
      // @todo Implement argument replacement in #1.
      // @see https://github.com/ec-europa/sparql_entity_storage/issues/1
      $result = $this->easyRdfClient->update($query);
    }
    catch (EasyRdfException $exception) {
      // Re-throw the exception, but with the query as message.
      throw new SparqlQueryException('Execution of query failed: ' . $query);
    }

    if ($this->logger) {
      $query_end = microtime(TRUE);
      $this->log($query, $args, $query_end - $query_start);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryUri(): string {
    return $this->easyRdfClient->getQueryUri();
  }

  /**
   * {@inheritdoc}
   */
  public function setLogger(Log $logger): void {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger(): ?Log {
    return $this->logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []): Client {
    $enpoint_path = !empty($connection_options['database']) ? trim($connection_options['database'], ' /') : '';
    // After trimming the value might be ''. Testing again.
    $enpoint_path = $enpoint_path ?: 'sparql';
    $protocol = empty($connection_options['https']) ? 'http' : 'https';

    $connect_string = "{$protocol}://{$connection_options['host']}:{$connection_options['port']}/{$enpoint_path}";

    return new Client($connect_string);
  }

  /**
   * {@inheritdoc}
   */
  public function setTarget(string $target = NULL): void {
    if (!isset($this->target)) {
      $this->target = $target;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget(): ?string {
    return $this->target;
  }

  /**
   * {@inheritdoc}
   */
  public function setKey(string $key): void {
    if (!isset($this->key)) {
      $this->key = $key;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(): ?string {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getConnectionOptions(): array {
    return $this->connectionOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function destroy():void {}

  /**
   * Logs a query duration in the DB logger.
   *
   * @param string $query
   *   The query to be logged.
   * @param array $args
   *   Arguments passed to the query.
   * @param float $duration
   *   The duration of the query run.
   *
   * @throws \RuntimeException
   *   If an attempt to log was made but the logger is not started.
   */
  protected function log(string $query, array $args, float $duration): void {
    if (!$this->logger) {
      throw new \RuntimeException('Cannot log query as the logger is not started.');
    }
    $this->logger->log($this->getStatement()->setQuery($query), $args, $duration);
  }

  /**
   * Returns and statically caches a DB statement stub used to log a query.
   *
   * The Drupal core database logger cannot be swapped because, instead of being
   * injected, is hardcoded in \Drupal\Core\Database\Database::startLog(). But
   * the \Drupal\Core\Database\Log::log() is expecting a database statement of
   * type \Drupal\Core\Database\StatementInterface as first argument and the
   * SPARQL database driver uses no StatementInterface class. We workaround this
   * limitation by faking a database statement object just to honour the logger
   * requirement. We use a statement stub that only stores the connection and
   * the query to be used when logging the event.
   *
   * @return \Drupal\sparql_entity_storage\Driver\Database\sparql\StatementStub
   *   A faked statement object.
   *
   * @see \Drupal\Core\Database\Database::startLog()
   * @see \Drupal\Core\Database\Log
   * @see \Drupal\Core\Database\StatementInterface
   * @see \Drupal\sparql_entity_storage\Driver\Database\sparql\StatementStub
   * @see \Drupal\sparql_entity_storage\Driver\Database\sparql\Connection::log()
   */
  protected function getStatement(): StatementStub {
    if (!isset($this->statementStub)) {
      $this->statementStub = (new StatementStub())->setDatabaseConnection($this);
    }
    return $this->statementStub;
  }

  /**
   * Creates an array of database connection options from a URL.
   *
   * @param string $url
   *   The URL.
   * @param string $root
   *   The root directory of the Drupal installation. Some database drivers,
   *   like for example SQLite, need this information.
   *
   * @return array
   *   The connection options.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the provided URL does not meet the minimum
   *   requirements.
   *
   * @see \Drupal\Core\Database\Database::convertDbUrlToConnectionInfo()
   *
   * @internal
   *   This method should not be called. Use
   *   \Drupal\Core\Database\Database::convertDbUrlToConnectionInfo() instead.
   */
  public static function createConnectionOptionsFromUrl($url, $root) {
    $url_components = parse_url($url);
    if (!isset($url_components['scheme'], $url_components['host'])) {
      throw new \InvalidArgumentException('Minimum requirement: driver://host');
    }

    $url_components += [
      'user' => '',
      'pass' => '',
    ];

    // Use reflection to get the namespace of the class being called.
    $reflector = new \ReflectionClass(get_called_class());

    $database = [
      'host' => $url_components['host'],
      'namespace' => $reflector->getNamespaceName(),
      'driver' => $url_components['scheme'],
    ];

    if (isset($url_components['port'])) {
      $database['port'] = $url_components['port'];
    }

    return $database;
  }

  /**
   * Creates a URL from an array of database connection options.
   *
   * @param array $connection_options
   *   The array of connection options for a database connection.
   *
   * @return string
   *   The connection info as a URL.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when the provided array of connection options does not
   *   meet the minimum requirements.
   *
   * @see \Drupal\Core\Database\Database::getConnectionInfoAsUrl()
   *
   * @internal
   *   This method should not be called. Use
   *   \Drupal\Core\Database\Database::getConnectionInfoAsUrl() instead.
   */
  public static function createUrlFromConnectionOptions(array $connection_options) {
    if (!isset($connection_options['driver'], $connection_options['database'])) {
      throw new \InvalidArgumentException("As a minimum, the connection options array must contain at least the 'driver' and 'database' keys");
    }

    $user = '';
    if (isset($connection_options['username'])) {
      $user = $connection_options['username'];
      if (isset($connection_options['password'])) {
        $user .= ':' . $connection_options['password'];
      }
      $user .= '@';
    }

    $host = empty($connection_options['host']) ? 'localhost' : $connection_options['host'];

    $db_url = $connection_options['driver'] . '://' . $user . $host;

    if (isset($connection_options['port'])) {
      $db_url .= ':' . $connection_options['port'];
    }

    $db_url .= '/' . $connection_options['database'];

    if (isset($connection_options['prefix']['default']) && $connection_options['prefix']['default'] !== '') {
      $db_url .= '#' . $connection_options['prefix']['default'];
    }

    return $db_url;
  }

}

/**
 * @} End of "addtogroup database".
 */

<?php

namespace Drupal\rdf_entity\Database\Driver\sparql;

use Drupal\Core\Database\Log as DatabaseLog;
use Drupal\rdf_entity\Exception\SparqlQueryException;
use EasyRdf\Http\Exception as EasyRdfException;
use EasyRdf\Sparql\Client;

/**
 * @addtogroup database
 * @{
 */
class Connection {

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
   * @var \Drupal\rdf_entity\Database\Driver\sparql\Log|null
   */
  protected $logger = NULL;

  /**
   * Constructs a Connection object.
   *
   * @param \EasyRdf\Sparql\Client $connection
   *   Object of \EasyRdf\Sparql\Client which is a database connection.
   * @param array $connection_options
   *   An array of options for the connection. May include the following:
   *   - prefix
   *   - namespace
   *   - Other driver-specific options.
   */
  public function __construct(Client $connection, array $connection_options) {
    $this->connection = $connection;
    $this->connectionOptions = $connection_options;
  }

  /**
   * Execute the actual query against the Sparql endpoint.
   *
   * @param string $query
   *   The query to execute.
   *
   * @return \EasyRdf\Sparql\Result
   *   The query result.
   */
  public function query($query) {
    if (!empty($this->logger)) {
      // @todo Fix this. Logger should have been auto started.
      // Probably related to the overwritten log object in $this->setLogger.
      // Look at
      // \Drupal\webprofiler\StackMiddleware\WebprofilerMiddleware::handle.
      $this->logger->start('webprofiler');
      $query_start = microtime(TRUE);
    }

    try {
      $results = $this->connection->query($query);
    }
    catch (EasyRdfException $e) {
      // Re-throw the exception, but with the query as message.
      throw new SparqlQueryException('Execution of query failed: ' . $query);
    }
    catch (\Exception $e) {
      throw $e;
    }

    if (!empty($this->logger)) {
      $query_end = microtime(TRUE);
      $this->query = $query;
      // @fixme Passing in an incorrect but seemingly compatible object.
      // This will most likely break in PHP7 (incorrect type hinting).
      // Replace array($query) with the placeholder version.
      // I should probably implement the statement interface...
      $this->logger->log($this, [$query], $query_end - $query_start);
    }

    return $results;
  }

  /**
   * Execute the actual update query against the Sparql endpoint.
   */
  public function update($query) {
    if (!empty($this->logger)) {
      // @todo Fix this. Logger should have been auto started.
      // Probably related to the overwritten log object in $this->setLogger.
      // Look at
      // \Drupal\webprofiler\StackMiddleware\WebprofilerMiddleware::handle.
      $this->logger->start('webprofiler');
      $query_start = microtime(TRUE);
    }

    try {
      $results = $this->connection->update($query);
    }
    catch (EasyRdfException $e) {
      // Re-throw the exception, but with the query as message.
      throw new SparqlQueryException('Execution of query failed: ' . $query);
    }
    catch (\Exception $e) {
      throw $e;
    }

    if (!empty($this->logger)) {
      $query_end = microtime(TRUE);
      $this->query = $query;
      // @fixme Passing in an incorrect but seemingly compatible object.
      // This will most likely break in PHP7 (incorrect type hinting).
      // Replace array($query) with the placeholder version.
      // I should probably implement the statement interface...
      $this->logger->log($this, [$query], $query_end - $query_start);
    }

    return $results;
  }

  /**
   * Helper to get the query. Called from the logger.
   */
  public function getQueryString() {
    return $this->query;
  }

  /**
   * Returns the database connection string.
   */
  public function getQueryUri() {
    return $this->connection->getQueryUri();
  }

  /**
   * Associates a logging object with this connection.
   *
   * @param \Drupal\Core\Database\Log $logger
   *   The logging object we want to use.
   */
  public function setLogger(DatabaseLog $logger) {
    // Because we're incompatible with the PDO logger,
    // we ignore this, and create our own object.
    // @todo Avoid doing this. It's not ok...
    $this->logger = new Log($this->getKey());
  }

  /**
   * Gets the current logging object for this connection.
   *
   * @return \Drupal\Core\Database\Log|null
   *   The current logging object for this connection. If there isn't one,
   *   NULL is returned.
   */
  public function getLogger() {
    return $this->logger;
  }

  /**
   * Initialize the database connection.
   *
   * @param array $connection_options
   *   The connection options as defined in settings.php.
   *
   * @return \EasyRdf\Sparql\Client
   *   The EasyRdf connection.
   */
  public static function open(array &$connection_options = []) {
    // @todo Get endpoint string from settings file.
    $connect_string = 'http://' . $connection_options['host'] . ':' . $connection_options['port'] . '/sparql';
    return new Client($connect_string);
  }

  /**
   * Tells this connection object what its target value is.
   *
   * This is needed for logging and auditing. It's sloppy to do in the
   * constructor because the constructor for child classes has a different
   * signature. We therefore also ensure that this function is only ever
   * called once.
   *
   * @param string $target
   *   (optional) The target this connection is for.
   */
  public function setTarget($target = NULL) {
    if (!isset($this->target)) {
      $this->target = $target;
    }
  }

  /**
   * Returns the target this connection is associated with.
   *
   * @return string|null
   *   The target string of this connection, or NULL if no target is set.
   */
  public function getTarget() {
    return $this->target;
  }

  /**
   * Tells this connection object what its key is.
   *
   * @param string $key
   *   The key this connection is for.
   */
  public function setKey($key) {
    if (!isset($this->key)) {
      $this->key = $key;
    }
  }

  /**
   * Returns the key this connection is associated with.
   *
   * @return string|null
   *   The key of this connection, or NULL if no key is set.
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * Returns the connection information for this connection object.
   *
   * Note that Database::getConnectionInfo() is for requesting information
   * about an arbitrary database connection that is defined. This method
   * is for requesting the connection information of this specific
   * open connection object.
   *
   * @return array
   *   An array of the connection information. The exact list of
   *   properties is driver-dependent.
   */
  public function getConnectionOptions() {
    return $this->connectionOptions;
  }

  /**
   * Destroys the db connection.
   */
  public function destroy() {

  }

}

/**
 * @} End of "addtogroup database".
 */

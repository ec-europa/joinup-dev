<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Driver\Database\sparql;

use Drupal\Core\Database\Log;
use EasyRdf\Graph;
use EasyRdf\Sparql\Client;
use EasyRdf\Sparql\Result;

/**
 * An interface for the Connection class.
 */
interface ConnectionInterface {

  /**
   * Returns the SPARQL client object.
   *
   * @return \EasyRdf\Sparql\Client
   *   The SPARQL client instantiated with the default connection info.
   */
  public function getSparqlClient(): Client;

  /**
   * Execute a select/insert/update query, returning a query result.
   *
   * @param string $query
   *   The string query to execute.
   * @param array $args
   *   An array of arguments for the query.
   * @param array $options
   *   An associative array of options to control how the query is run.
   *
   * @return \EasyRdf\Sparql\Result
   *   The query result.
   *
   * @throws \InvalidArgumentException
   *   If $args value is passed but arguments replacement is not yet
   *   supported. To be removed in #55.
   *
   * @see https://github.com/ec-europa/sparql_entity_storage/issues/1
   */
  public function query(string $query, array $args = [], array $options = []): Result;

  /**
   * Execute a construct query, returning a graph of triples.
   *
   * @param string $query
   *   The string query to execute.
   * @param array $args
   *   An array of arguments for the query.
   * @param array $options
   *   An associative array of options to control how the query is run.
   *
   * @return \EasyRdf\Graph
   *   The set of triples.
   *
   * @throws \InvalidArgumentException
   *   If $args value is passed but arguments replacement is not yet
   *   supported. To be removed in #55.
   *
   * @see https://github.com/ec-europa/sparql_entity_storage/issues/1
   */
  public function constructQuery(string $query, array $args = [], array $options = []): Graph;

  /**
   * Execute the actual update query against the Sparql endpoint.
   *
   * @param string $query
   *   The query string.
   * @param array $args
   *   An array of arguments for the query.
   * @param array $options
   *   An associative array of options to control how the query is run.
   *
   * @return \EasyRdf\Sparql\Result
   *   The result object.
   *
   * @throws \InvalidArgumentException
   *   If $args value is passed but arguments replacement is not yet
   *   supported. To be removed in #55.
   *
   * @see https://github.com/ec-europa/sparql_entity_storage/issues/1
   */
  public function update(string $query, array $args = [], array $options = []): Result;

  /**
   * Returns the database connection string.
   *
   * @return string
   *   The query uri string.
   */
  public function getQueryUri(): string;

  /**
   * Associates a logging object with this connection.
   *
   * @param \Drupal\Core\Database\Log $logger
   *   The logging object we want to use.
   */
  public function setLogger(Log $logger): void;

  /**
   * Gets the current logging object for this connection.
   *
   * @return \Drupal\Core\Database\Log|null
   *   The current logging object for this connection. If there isn't one,
   *   NULL is returned.
   */
  public function getLogger(): ?Log;

  /**
   * Initialize the database connection.
   *
   * @param array $connection_options
   *   The connection options as defined in settings.php.
   *
   * @return \EasyRdf\Sparql\Client
   *   The EasyRdf client instance.
   */
  public static function open(array &$connection_options = []): Client;

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
  public function setTarget(string $target = NULL): void;

  /**
   * Returns the target this connection is associated with.
   *
   * @return string|null
   *   The target string of this connection, or NULL if no target is set.
   */
  public function getTarget(): ?string;

  /**
   * Tells this connection object what its key is.
   *
   * @param string $key
   *   The key this connection is for.
   */
  public function setKey(string $key): void;

  /**
   * Returns the key this connection is associated with.
   *
   * @return string|null
   *   The key of this connection, or NULL if no key is set.
   */
  public function getKey(): ?string;

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
  public function getConnectionOptions(): array;

  /**
   * Destroys the DB connection.
   */
  public function destroy(): void;

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_sparql\Driver\Database\joinup_sparql;

use Drupal\sparql_entity_storage\Driver\Database\sparql\Connection as BaseConnection;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Exception\SparqlQueryException;
use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
use EasyRdf\Http\Exception as EasyRdfException;
use EasyRdf\Sparql\Client;
use EasyRdf\Sparql\Result;

/**
 * @addtogroup database
 * @{
 */

/**
 * SPARQL connection service set up for virtuoso and Joinup.
 */
class Connection extends BaseConnection implements ConnectionInterface {

  /**
   * {@inheritdoc}
   */
  public function query(string $query, array $args = [], array $options = []): Result {
    $attempts = 2;
    do {
      $success = TRUE;
      try {
        return parent::query($query);
      }
      catch (SparqlQueryException $e) {
        // During a Virtuoso checkpoint, the server locks down, causing HTTP
        // requests on the SPARQL endpoint to fail with a 404 response. We wait
        // a reasonable amount of time and then we retry one more time.
        // @see http://docs.openlinksw.com/virtuoso/checkpoint/
        sleep(5);
        if ($attempts === 0) {
          throw new \Exception($e->getMessage());
        }
        $attempts--;
        $success = FALSE;
      }
    } while (!$success);
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
      throw new SparqlQueryException('Execution of query failed: ' . htmlentities($query));
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
    try {
      return parent::update($query);
    }
    catch (SparqlQueryException $e) {
      // During a Virtuoso checkpoint, the server locks down, causing HTTP
      // requests on the SPARQL endpoint to fail with a 404 response. We wait a
      // reasonable amount of time and then we retry one more time.
      // @see http://docs.openlinksw.com/virtuoso/checkpoint/
      sleep(5);
      return parent::update($query);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function open(array &$connection_options = []): Client {
    $client = new HttpClient();
    $client->setConfig(['timeout' => '30']);
    Http::setDefaultHttpClient($client);
    return parent::open($connection_options);
  }

}

/**
 * @} End of "addtogroup database".
 */

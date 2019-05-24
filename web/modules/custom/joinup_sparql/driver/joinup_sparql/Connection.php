<?php

declare(strict_types = 1);

namespace Drupal\Driver\Database\joinup_sparql;

use Drupal\Driver\Database\sparql\Connection as BaseConnection;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Exception\SparqlQueryException;
use EasyRdf\Http;
use EasyRdf\Http\Client as HttpClient;
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
    try {
      return parent::query($query);
    }
    catch (SparqlQueryException $e) {
      // During a Virtuoso checkpoint, the server locks down, causing HTTP
      // requests on the SPARQL endpoint to fail with a 404 response. We wait a
      // reasonable amount of time and then we retry one more time.
      // @see http://docs.openlinksw.com/virtuoso/checkpoint/
      sleep(5);
      return parent::query($query);
    }
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

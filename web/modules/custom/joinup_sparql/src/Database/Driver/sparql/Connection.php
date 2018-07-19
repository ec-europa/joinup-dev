<?php

declare(strict_types = 1);

namespace Drupal\joinup_sparql\Database\Driver\sparql;

use Drupal\rdf_entity\Database\Driver\sparql\Connection as BaseConnection;
use Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Exception\SparqlQueryException;
use EasyRdf\Sparql\Result;

/**
 * @addtogroup database
 * @{
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
      return $this->query($query);
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
      return $this->update($query);
    }
  }

}

/**
 * @} End of "addtogroup database".
 */

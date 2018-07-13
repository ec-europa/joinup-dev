<?php

namespace Drupal\joinup_sparql\Database\Driver\sparql;

use Drupal\rdf_entity\Database\Driver\sparql\Connection as BaseConnection;
use Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface;
use Drupal\rdf_entity\Exception\SparqlQueryException;
use EasyRdf\Http\Exception as EasyRdfException;
use EasyRdf\Sparql\Result;

/**
 * @addtogroup database
 * @{
 */
class Connection extends BaseConnection implements ConnectionInterface {

  /**
   * {@inheritdoc}
   */
  public function query(string $query): Result {
    static $recurse = FALSE;
    if (!empty($this->logger)) {
      $this->logger->start('webprofiler');
      $query_start = microtime(TRUE);
    }

    try {
      $results = $this->connection->query($query);
    }
    catch (EasyRdfException $e) {
      // Handle the virtuoso checkpoint case.
      if ($recurse == TRUE) {
        $recurse = FALSE;
        throw new SparqlQueryException('Execution of query failed: ' . $query);
      }
      $this->logger->clear('webprofiler');
      sleep(5);
      return $this->query($query);
    }
    catch (\Exception $e) {
      throw $e;
    }

    if (!empty($this->logger)) {
      $query_end = microtime(TRUE);
      $this->query = $query;
      $this->logger->log($this, [$query], $query_end - $query_start);
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function update(string $query): Result {
    static $recurse = FALSE;
    if (!empty($this->logger)) {
      $this->logger->start('webprofiler');
      $query_start = microtime(TRUE);
    }

    try {
      $results = $this->connection->update($query);
    }
    catch (EasyRdfException $e) {
      // Handle the virtuoso checkpoint case.
      if ($recurse == TRUE) {
        $recurse = FALSE;
        throw new SparqlQueryException('Execution of query failed: ' . $query);
      }
      $this->logger->clear('webprofiler');
      sleep(5);
      return $this->update($query);
    }
    catch (\Exception $e) {
      throw $e;
    }

    if (!empty($this->logger)) {
      $query_end = microtime(TRUE);
      $this->query = $query;
      $this->logger->log($this, [$query], $query_end - $query_start);
    }

    return $results;
  }

}

/**
 * @} End of "addtogroup database".
 */

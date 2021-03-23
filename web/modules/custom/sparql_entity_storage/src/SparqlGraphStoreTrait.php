<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Core\Database\Database;
use EasyRdf\GraphStore;

/**
 * Provides helper methods for graph stores on the current SPARQL connection.
 */
trait SparqlGraphStoreTrait {

  /**
   * Creates a new Graph Store object using the SPARQL connection.
   *
   * @return \EasyRdf\GraphStore
   *   The new graph store object.
   */
  public static function createGraphStore(): GraphStore {
    $sparql_connection = Database::getConnection('default', 'sparql_default');
    $connection_options = $sparql_connection->getConnectionOptions();
    $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
    // Use a local SPARQL 1.1 Graph Store.
    return new GraphStore($connect_string);
  }

}

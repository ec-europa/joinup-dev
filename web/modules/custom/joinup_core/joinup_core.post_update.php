<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Insert the new EIF vocabulary into the database.
 */
function joinup_core_post_update_0106200(): void {
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $filepath = __DIR__ . '/../../../../resources/fixtures/eif_voc.rdf';
  $graph = new Graph('http://eif_voc');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);
}

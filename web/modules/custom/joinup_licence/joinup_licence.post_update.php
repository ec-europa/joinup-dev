<?php

/**
 * @file
 * Post update functions for the Joinup licence module.
 */

use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Update import the spdx legal type vocabulary.
 */
function joinup_licence_post_update_import_legal_type_vocabulary() {
  $graph_uri = 'http://licence-legal-type';

  /** @var \Drupal\rdf_entity\Database\Driver\sparql\ConnectionInterface $connection */
  $connection = \Drupal::service('sparql_endpoint');

  // Avoid duplicates in case a manual fixtures import has already occurred.
  $connection->getSparqlClient()->clear($graph_uri);
  $graph = new Graph($graph_uri);
  $filename = DRUPAL_ROOT . '/../resources/fixtures/licence-legal-type.rdf';
  $graph->parseFile($filename);

  $connection_options = $connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $graph_store->insert($graph);
}

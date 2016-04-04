<?php
/**
 * @file
 * Purge the triple store from the cli.
 */

use \Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function rdf_entity_drush_command() {
  $items = array();

  $items['rdf-entity-purge'] = array(
    'description' => 'Delete the whole index at the Sparql endpoint..',
    'examples' => array(
      'drush rdf-entity-purge' => dt('Purge the triple store..'),
      'drush repl' => dt('Alias to purge the triple store.'),
    ),
    'aliases' => array('rep'),
  );
  return $items;
}

/**
 * Delete all data from the endpoint.
 */
function drush_rdf_entity_purge() {
  /** @var Drupal\rdf_entity\Database\Driver\sparql\Connection $endpoint */
  $endpoint = \Drupal::service('sparql_endpoint');
  $query = "SELECT COUNT (?s) AS ?count WHERE {?s ?p ?o}";

  $response = $endpoint->query($query);
  $count = 0;
  foreach ($response as $value) {
    $count = (string) $value->count;
  }
  // Issuing a delete on an empty store throws errors, so only delete if needed.
  if ($count) {
    $query = <<<QUERY
DELETE {
  GRAPH ?g {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH ?g {
    ?entity ?field ?value .
  }
}

SPARQL CLEAR GRAPH  <http://example.com>;
DROP SILENT GRAPH
QUERY;
    $endpoint->query($query);
    drush_log('All triples have been removed.', LogLevel::INFO);

  }
  else {
    drush_log('Triple store is empty, nothing to delete.', LogLevel::INFO);
  }
}

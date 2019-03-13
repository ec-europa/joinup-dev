<?php

/**
 * @file
 * Post update functions for the solution module.
 */

/**
 * Sets the default value for 'Solutions related by type' field.
 */
function solution_post_update_solution_by_type_default_value() {
  $query = <<<QUERY
WITH <http://joinup.eu/solution/published>
INSERT { ?solution_id <http://joinup.eu/solution/related_by_type> "true"^^<http://www.w3.org/2001/XMLSchema#boolean> }
WHERE { ?solution_id a <http://www.w3.org/ns/dcat#Dataset> }
QUERY;

  /** @var \Drupal\Core\Database\Connection $connection */
  $connection = \Drupal::service('sparql_endpoint');
  $connection->query($query);
}

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

/**
 * Deletes wrong affiliations on the database.
 */
function solution_post_update_delete_wrong_affiliations() {
  // We have identified a rare case that has probably never occurred in the
  // database.
  // The result of this case is that a relation between a collection and a
  // solution can exist in a graph without the rest of the entity being there.
  // This should not be possible to happen through the UI since there is no way
  // for a user to set or unset the affiliation apart from the predefined ways.
  //
  // Still, the following query will clean any case of an orphaned triple.
  // We cannot use the Drupal API, as there is no other triple to define the
  // entity. The query searches for triples about entities without a bundle
  // definition in the collection graphs.
  $query = <<<QUERY
DELETE {
  GRAPH <http://joinup.eu/collection/published> {
    ?entity_id <http://www.w3.org/ns/dcat#dataset> ?solution_id
  }
  GRAPH <http://joinup.eu/collection/draft> {
    ?entity_id <http://www.w3.org/ns/dcat#dataset> ?solution_id
  }
}
WHERE {
  ?entity_id <http://www.w3.org/ns/dcat#dataset> ?solution_id .
  FILTER NOT EXISTS { ?entity_id a <http://www.w3.org/ns/dcat#Catalog> }
}
QUERY;

  \Drupal::service('sparql.endpoint')->query($query);
}

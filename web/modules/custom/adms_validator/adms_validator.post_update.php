<?php

/**
 * @file
 * Post update functions for ADMS-AP Validator module.
 */

use Drupal\Core\Database\Database;

/**
 * Purge stale data from legacy 'http://adms-validator/' graph.
 */
function adms_validator_post_update_purge_validator_graph() {
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $sparql_connection->query('CLEAR GRAPH <http://adms-validator/>;');
}

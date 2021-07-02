<?php

/**
 * @file
 * Post update functions for Joinup SPARQL.
 */

declare(strict_types = 1);

/**
 * Update the Licence legal type vocabulary.
 */
function joinup_sparql_post_update_0107301(array &$sandbox): void {
  \Drupal::service('joinup_sparql.vocabulary_fixtures.replace')->updatejla();
}

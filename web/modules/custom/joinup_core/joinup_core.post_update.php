<?php

/**
 * @file
 * Post update functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API but
 * need to run _before_ the configuration is imported.
 *
 * For example this can be used to enable a new module that needs to have its
 * code available for the configuration to be successfully imported or updated.
 *
 * In most cases though update code should be placed in joinup_core.deploy.php.
 */

declare(strict_types = 1);

/**
 * Implements hook_post_update_NAME().
 */
function joinup_core_post_update_0107400(&$sandbox): void {
  $graphs = [
    'http://joinup.eu/collection/draft',
    'http://joinup.eu/collection/published',
  ];

  // This query updates the text format of the abstract field for collections.
  // The field was updated to have a new sole format but the existing data were
  // not updated.
  foreach ($graphs as $graph) {
    $query = <<<QUERY
WITH <{$graph}>
DELETE { ?entity_id <http://joinup.eu/text_format> "basic_html"^^<http://www.w3.org/2001/XMLSchema#string> }
INSERT { ?entity_id <http://joinup.eu/text_format> "essential_html"^^<http://www.w3.org/2001/XMLSchema#string> }
WHERE { ?entity_id <http://joinup.eu/text_format> "basic_html"^^<http://www.w3.org/2001/XMLSchema#string> }
QUERY;

    \Drupal::getContainer()->get('sparql.endpoint')->query($query);
  }

}

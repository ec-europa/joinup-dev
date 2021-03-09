<?php

/**
 * @file
 * Deploy functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API and
 * need to run _after_ the configuration is imported.
 *
 * This is applicable in most cases. However in case the update code enables
 * some functionality that is required for configuration to be successfully
 * imported, it should instead be placed in joinup_core.post_update.php.
 */

declare(strict_types = 1);

/**
 * Fix the datatype of the owner ID in owners and contact information.
 */
function joinup_core_deploy_0106900(array &$sandbox): void {
  $database = \Drupal::getContainer()->get('sparql.endpoint');
  $variables = [
    'http://joinup.eu/owner/published' => 'http://joinup.eu/owner/uid',
    'http://joinup.eu/contact_information/published' => 'http://joinup.eu/contact_information/uid',
  ];

  foreach ($variables as $graph => $predicate) {
    $query = <<<QUERY
WITH <{$graph}>
DELETE { ?owner <{$predicate}> ?value }
INSERT { ?owner <{$predicate}> ?new_value }
WHERE {
 ?owner <{$predicate}> ?value .
 FILTER (datatype(?value) = <integer>) .
 BIND(STRDT(STR(?value), xsd:integer) AS ?new_value)
}
QUERY;

    $database->query($query);
  }
}

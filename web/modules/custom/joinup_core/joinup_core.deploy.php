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

use Drupal\Core\Database\Database;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use EasyRdf\Graph;

/**
 * Update the EIRA vocabulary.
 */
function joinup_core_deploy_0107400(array &$sandbox): void {
  // Clean up the existing graph.
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $sparql_connection->query('WITH <http://eira_skos> DELETE { ?s ?p ?o } WHERE { ?s ?p ?o } ');

  $filepath = __DIR__ . '/../../../../resources/fixtures/EIRA_SKOS.rdf';
  $graph_store = SparqlGraphStoreTrait::createGraphStore();
  $graph = new Graph('http://eira_skos');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);

  // Repeat steps taken after importing the fixtures that target eira terms.
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
  $sparql_connection->query('WITH <http://eira_skos> INSERT INTO <http://eira_skos> { ?subject skos:topConceptOf <http://data.europa.eu/dr8> } WHERE { ?subject a skos:Concept .};');
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');

  // There is one term removed and replaced. Update database records.
  $graphs = [
    'http://joinup.eu/solution/published',
    'http://joinup.eu/solution/draft',
  ];

  foreach ($graphs as $graph) {
    $query = <<<QUERY
WITH <$graph>
DELETE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/PublicPolicyImplementationApproach> }
INSERT { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/InteroperableDigitalPublicServicesImplementationOrientation> }
WHERE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/PublicPolicyImplementationApproach> }
QUERY;
    $sparql_connection->query($query);
  }
}

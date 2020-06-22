<?php

declare(strict_types = 1);

namespace Drupal\solution;

use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;

/**
 * Implements helper methods related to installation requirements for solutions.
 */
class RequirementsHelper {

  /**
   * The SPARQL connection class.
   *
   * @var \Drupal\Driver\Database\joinup_sparql\Connection
   */
  protected $sparqlConnection;

  /**
   * Constructs a new RequirementsHelper.
   *
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql
   *   The SPARQL connection class.
   */
  public function __construct(ConnectionInterface $sparql) {
    $this->sparqlConnection = $sparql;
  }

  /**
   * Returns solutions without an affiliated collection.
   *
   * As no solution should exist without a collection, the following method is
   * used to query if any of them exists.
   *
   * @return array
   *   An array of solution label keyed by their IDs.
   */
  public function getOrphanedSolutions(): array {
    $query = <<<QUERY
SELECT ?solution_id
FROM <http://joinup.eu/collection/published>
FROM <http://joinup.eu/collection/draft>
FROM <http://joinup.eu/solution/published>
FROM <http://joinup.eu/solution/draft>
WHERE {
  ?solution_id a <http://www.w3.org/ns/dcat#Dataset> .
  FILTER NOT EXISTS {
    ?collection a <http://www.w3.org/ns/dcat#Catalog> ;
    <http://www.w3.org/ns/dcat#dataset> ?solution_id
  }
}
QUERY;

    $return = [];
    $results = $this->sparqlConnection->query($query);
    if (!empty($results)) {
      foreach ($results as $result) {
        $return[$result->solution_id->getUri()] = $result->solution_id->getUri();
      }
    }
    return $return;
  }

}

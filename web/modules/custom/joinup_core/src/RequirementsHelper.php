<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Database\Connection;
use Drupal\Driver\Database\joinup_sparql\Connection as SparqlConnection;

/**
 * Implements helper methods related to the requirements.
 */
class RequirementsHelper {

  /**
   * The SQL connection class for the primary database storage.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $sqlConnection;

  /**
   * The SPARQL connection class.
   *
   * @var \Drupal\Driver\Database\joinup_sparql\Connection
   */
  protected $sparqlConnection;

  /**
   * RequirementsHelper constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The SQL connection class for the primary database storage.
   * @param \Drupal\Driver\Database\joinup_sparql\Connection $sparql
   *   The SPARQL connection class.
   */
  public function __construct(Connection $connection, SparqlConnection $sparql) {
    $this->sqlConnection = $connection;
    $this->sparqlConnection = $sparql;
  }

  /**
   * Fetches node entries with faulty forward published revisions.
   *
   * The node entries that are queried are required to have a published revision
   * as a default one but also have other published revision(s) that are newer
   * than the default one. This is an inconsistency that should never happen in
   * a normal site as, normally, every new revision receives a new version id
   * higher than the previous ones as it is a serial number and even reverting
   * a revision, mainly creates a new one.
   *
   * The query only queries for the published revisions because the entities in
   * question might still have forward draft revisions. These are valid cases as
   * draft revisions are unpublished and it is ok to have unpublished versions
   * of an entity. For that purpose, the query below only works with the
   * published versions.
   *
   * @return array
   *   An associative array of version_ids indexed by their id. The vid is the
   *   latest published revision in the database.
   */
  public function getNodesWithProblematicRevisions(): array {
    $query = <<<QUERY
SELECT n.nid AS nid,
  (
    SELECT max(vid)
    FROM node_revision
    LEFT JOIN node_revision__field_state ON node_revision.vid = node_revision__field_state.revision_id
    WHERE nid = n.nid AND field_state_value = 'validated'
    AND revision_default = 1
  ) as latest_vid
FROM node as n
LEFT JOIN node_revision AS nr
ON n.nid = nr.nid
# Published revision is behind latest default revision
AND n.vid < (
  SELECT max(vid)
  FROM node_revision
  LEFT JOIN node_revision__field_state ON node_revision.vid = node_revision__field_state.revision_id
  WHERE nid = n.nid AND field_state_value = 'validated'
  AND revision_default = 1
)
LEFT JOIN node_field_data AS nfd
ON n.nid = nfd.nid
WHERE nr.vid > n.vid
GROUP BY nid, latest_vid
QUERY;
    return $this->sqlConnection->query($query)->fetchAllAssoc('nid');
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

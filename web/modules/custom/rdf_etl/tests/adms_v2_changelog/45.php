<?php

/**
 * @file
 * Provides assertions for change: #45.
 *
 * URI: dct:publisher
 * Type: Mandatory property (Asset Repository)
 * Action: Updated
 * Description:
 * - Updated: An asset distribution was declared as dcat:Distribution rather
 *   than adms:AssetDistribution.
 * - Removed statement about backwards compatibility.
 * Change requests: CR42
 */

$query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?field_value
FROM NAMED <{$this->graph}>
WHERE {
  GRAPH ?graph {
    ?entity_id ?predicate ?field_value .
    VALUES ?entity_id { <http://example.com/rdf_entity/collection/45> } .
  }
}
QUERY;
$results = $this->sparql->query($query);
$results = array_filter($results->getArrayCopy(), function (\stdClass $result) {
  return (string) $result->predicate === 'http://purl.org/dc/terms/publisher';
});

// Check that publisher cardinality is 1..1.
$this->assertCount(1, $results);

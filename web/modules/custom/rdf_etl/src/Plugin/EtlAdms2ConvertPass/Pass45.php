<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlAdms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginBase;

/**
 * Conversion Pass #45.
 *
 * URI: dct:publisher
 * Type: Mandatory property (Asset Repository)
 * Action: Updated
 * Description:
 * - Updated: An asset distribution was declared as dcat:Distribution rather
 *   than adms:AssetDistribution.
 * - Removed statement about backwards compatibility.
 * Change requests: CR42
 *
 * @Adms2ConvertPass(
 *   id = "pass_45",
 * )
 */
class Pass45 extends EtlAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $graph = $data['sync_graph'];
    $query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?value
FROM NAMED <$graph>
WHERE {
  GRAPH ?graph {
    ?entity_id ?predicate ?value .
  }
}
ORDER BY ?entity_id
QUERY;

    $results = $this->sparql->query($query);
    $last_entity_id = NULL;
    $entity = [];
    foreach ($results as $delta => $result) {
      $entity_id = (string) $result->entity_id;

      // Cursor moved to a new entity?
      if ($entity_id !== $last_entity_id) {
        $this->deleteAdditionalPublishers($graph, $entity_id, $entity);
        $entity = [];
      }

      $predicate = (string) $result->predicate;
      $value = (string) $result->value;
      if ($predicate === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
        $entity['type'] = $value;
      }
      if ($predicate === 'http://purl.org/dc/terms/publisher') {
        $entity['publisher'][$value] = $value;
      }
      $last_entity_id = $entity_id;

      // Just before finishing call thde deletion method for the last entity.
      if ($delta === count($results) - 1) {
        $this->deleteAdditionalPublishers($graph, $entity_id, $entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?value
FROM NAMED <%s>
WHERE {
  GRAPH ?graph {
    ?entity_id ?predicate ?value .
    VALUES ?entity_id { <http://example.com/rdf_entity/collection/45> } .
  }
}
QUERY;

    $results = $this->sparql->query(sprintf($query, static::TEST_GRAPH));
    $results = array_filter($results->getArrayCopy(), function (\stdClass $result) {
      return (string) $result->predicate === 'http://purl.org/dc/terms/publisher';
    });

    // Check that publisher cardinality is 1..1.
    $test->assertCount(1, $results);
    // Check that the first publisher has been picked-up.
    $result = reset($results);
    $test->assertEquals('http://example.com/rdf_entity/owner/45/1', (string) $result->value);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/rdf_entity/collection/45">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#AssetRepository"/>
    <dcat:accessURL rdf:resource="http://example.com/rdf_entity/collection/45"/>
    <dct:title xml:lang="en">Repository 45</dct:title>
    <dct:publisher rdf:resource="http://example.com/rdf_entity/owner/45/1"/>
    <dct:publisher rdf:resource="http://example.com/rdf_entity/owner/45/2"/>
    <dct:publisher rdf:resource="http://example.com/rdf_entity/owner/45/3"/>
</rdf:Description>
RDF;
  }

  /**
   * Deletes the additional publishers from an asset repository.
   *
   * @param string $graph
   *   The graph URI.
   * @param string $entity_id
   *   The ID of the entity.
   * @param array $entity
   *   The entity data.
   */
  protected function deleteAdditionalPublishers(string $graph, string $entity_id, array $entity): void {
    // Deal only with asset repositories...
    if ($entity && $entity['type'] === 'http://www.w3.org/ns/adms#AssetRepository') {
      // ...that have more than one publisher.
      if (isset($entity['publisher']) && count($entity['publisher']) > 1) {
        // Extract only the additional publishers and build the query condition.
        array_shift($entity['publisher']);
        $values = array_map(function (string $value) use ($entity_id): string {
          return "  <$entity_id> <http://purl.org/dc/terms/publisher> <$value> .";
        }, $entity['publisher']);
        $values = implode("\n", $values);

        $this->sparql->query("DELETE DATA FROM <$graph> { $values }");
      }
    }
  }

}

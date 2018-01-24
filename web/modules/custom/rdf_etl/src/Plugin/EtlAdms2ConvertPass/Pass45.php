<?php

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
  public function convert(): void {
    // Implement here the transformation needed to fix the change #45.
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $query = <<<QUERY
SELECT ?graph ?entity_id ?predicate ?field_value
FROM NAMED <%s>
WHERE {
  GRAPH ?graph {
    ?entity_id ?predicate ?field_value .
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

}

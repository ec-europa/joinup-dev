<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlAdms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginBase;

/**
 * Conversion Pass #41.
 *
 * URI: dct:issued
 * Type: Optional property (Asset Distribution)
 * Action: Updated
 * Description:
 * - Updated: Cardinality: 0..n -> 0..1".
 *
 * @see https://joinup.ec.europa.eu/discussion/cr25-distribution-modify-cardinality-dctissued-01
 *
 * @Adms2ConvertPass(
 *   id = "pass_41",
 * )
 */
class Pass41 extends EtlAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $this->processGraph($data['sync_graph'], 'http://purl.org/dc/terms/issued');
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/distribution/41',
      'http://purl.org/dc/terms/issued'
    );
    // Check that 'issued' cardinality is 1..1.
    $test->assertCount(1, $results);
    // Check that oldest issue date has been picked-up.
    $result = reset($results);
    $test->assertEquals('2013-01-04T11:39:16+01:00', $result['object']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/distribution/41">
   <rdf:type rdf:resource="https://www.w3.org/ns/dcat#Distribution"/>
   <dcat:accessURL rdf:resource="http://example.com/distribution/41"/>
   <dct:title xml:lang="en">Distribution 41</dct:title>
   <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2016-12-30T00:47:01+01:00</dct:issued>
   <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2013-01-04T11:39:16+01:00</dct:issued>
   <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2014-07-15T07:03:10+01:00</dct:issued>
</rdf:Description>
RDF;
  }

  /**
   * {@inheritdoc}
   */
  protected function processGraphCallback(string $graph, ?string $subject, string $predicate, array $entity): void {
    // Deal only with asset distributions...
    if ($subject && $entity && $entity['type'] === static::ASSET_DISTRIBUTION) {
      // ...that have more than one 'issued' entries.
      if (isset($entity[$predicate]) && count($entity[$predicate]) > 1) {
        // Deletes the additional 'issued' entries from an asset repository.
        $issued_entries_to_delete = $entity[$predicate];
        // We keep the oldest 'issued' entry.
        sort($issued_entries_to_delete);
        array_shift($issued_entries_to_delete);
        // Serialize.
        array_walk($issued_entries_to_delete, function (string &$entry): void {
          $entry = sprintf('"%s"^^xsd:dateTime', $entry);
        });
        $this->deleteTriples($graph, $subject, $predicate, $issued_entries_to_delete);
      }
    }
  }

}

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
    $entity = [];
    $last_subject = NULL;
    $results = $this->getTriplesFromGraph($data['sync_graph']);
    foreach ($results as $delta => $result) {
      // Cursor moved to a new set of triples?
      if ($result['subject'] !== $last_subject) {
        $this->deleteAdditionalIssuedEntries($data['sync_graph'], $last_subject, $entity);
        $entity = [];
      }

      if ($result['predicate'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
        $entity['type'] = $result['object'];
      }
      if ($result['predicate'] === 'http://purl.org/dc/terms/issued') {
        $entity['issued'][] = $result['object'];
      }

      // Store the last ID so we can check on the next iteration.
      $last_subject = $result['subject'];

      // Just before finishing, call the deletion method for the last entity.
      if ($delta === count($results) - 1) {
        $this->deleteAdditionalIssuedEntries($data['sync_graph'], $result['subject'], $entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(static::TEST_GRAPH, 'http://example.com/rdf_entity/distribution/41');
    $results = array_filter($results, function (array $triple) {
      return $triple['predicate'] === 'http://purl.org/dc/terms/issued';
    });

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
<rdf:Description rdf:about="http://example.com/rdf_entity/distribution/41">
   <rdf:type rdf:resource="http://www.w3.org/ns/adms#AssetDistribution"/>
   <dcat:accessURL rdf:resource="http://example.com/rdf_entity/distribution/41"/>
   <dct:title xml:lang="en">Distribution 41</dct:title>
   <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2016-12-30T00:47:01+01:00</dct:issued>
   <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2013-01-04T11:39:16+01:00</dct:issued>
   <dct:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2014-07-15T07:03:10+01:00</dct:issued>
</rdf:Description>
RDF;
  }

  /**
   * Deletes the additional 'issued' entries from an asset repository.
   *
   * @param string $graph
   *   The graph URI.
   * @param string|null $subject
   *   The subject of the entity set.
   * @param array $entity
   *   The triples data grouped in an entity.
   */
  protected function deleteAdditionalIssuedEntries(string $graph, ?string $subject, array $entity): void {
    // Deal only with asset distributions...
    if ($subject && $entity && $entity['type'] === 'http://www.w3.org/ns/adms#AssetDistribution') {
      // ...that have more than one 'issued' entries.
      if (isset($entity['issued']) && count($entity['issued']) > 1) {
        $issued_entries_to_delete = $entity['issued'];
        // We keep the oldest 'issued' entry.
        sort($issued_entries_to_delete);
        array_shift($issued_entries_to_delete);
        // Serialize.
        array_walk($issued_entries_to_delete, function (string &$entry): void {
          $entry = sprintf('"%s"^^xsd:dateTime', $entry);
        });
        $this->deleteTriples($graph, $subject, 'http://purl.org/dc/terms/issued', $issued_entries_to_delete);
      }
    }
  }

}

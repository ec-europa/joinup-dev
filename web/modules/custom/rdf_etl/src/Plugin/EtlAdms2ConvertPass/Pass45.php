<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlAdms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginBase;

/**
 * Conversion Pass #45.
 *
 * URI: dct:publisher
 * Type: Mandatory property (Asset Repository)
 * Action: Updated
 * Description:
 * - Updated: Cardinality: 1..n -> 1..1,
 * - Updated the definition: the publisher is the Agent that publishes the asset
 *   or solutions, not the Agent that publishes the metadata about it.
 *
 * @see https://joinup.ec.europa.eu/discussion/cr2-repository-change-cardinality-dctpublisher-11
 * @see https://joinup.ec.europa.eu/discussion/cr35-clarify-meaning-publisher-context-interoperability-solutions-and-repository
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
    $triples = [];
    $last_subject = NULL;
    $results = $this->getTriplesFromGraph($data['sync_graph']);
    foreach ($results as $delta => $result) {
      // Cursor moved to a new set of triples?
      if ($result['subject'] !== $last_subject) {
        $this->deleteAdditionalPublishers($data['sync_graph'], $last_subject, $triples);
        $triples = [];
      }

      if ($result['predicate'] === 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
        $triples['type'] = $result['object'];
      }
      if ($result['predicate'] === 'http://purl.org/dc/terms/publisher') {
        $triples['publisher'][] = $result['object'];
      }

      // Store the last ID so we can check on the next iteration.
      $last_subject = $result['subject'];

      // Just before finishing, call the deletion method for the last entity.
      if ($delta === count($results) - 1) {
        $this->deleteAdditionalPublishers($data['sync_graph'], $result['subject'], $triples);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(static::TEST_GRAPH, 'http://example.com/rdf_entity/collection/45');
    $results = array_filter($results, function (array $triple) {
      return $triple['predicate'] === 'http://purl.org/dc/terms/publisher';
    });

    // Check that publisher cardinality is 1..1.
    $test->assertCount(1, $results);
    // Check that the first publisher has been picked-up.
    $result = reset($results);
    $test->assertEquals('http://example.com/rdf_entity/owner/45/1', $result['object']);
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
   * @param string|null $subject
   *   The subject of the entity set.
   * @param array $triples
   *   The triples data.
   */
  protected function deleteAdditionalPublishers(string $graph, ?string $subject, array $triples): void {
    // Deal only with asset repositories...
    if ($subject && $triples && $triples['type'] === 'http://www.w3.org/ns/adms#AssetRepository') {
      // ...that have more than one publisher.
      if (isset($triples['publisher']) && count($triples['publisher']) > 1) {
        $publishers_to_delete = $triples['publisher'];
        // Extract only the additional publishers and build the query condition.
        array_shift($publishers_to_delete);
        $this->deleteTriples($graph, $subject, 'http://purl.org/dc/terms/publisher', SparqlArg::toResourceUris($publishers_to_delete));
      }
    }
  }

}

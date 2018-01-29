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
    $this->processGraph($data['sync_graph'], 'http://purl.org/dc/terms/publisher');
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/repository/45',
      'http://purl.org/dc/terms/publisher'
    );
    // Check that publisher cardinality is 1..1.
    $test->assertCount(1, $results);
    // Check that the first publisher has been picked-up.
    $result = reset($results);
    $test->assertEquals('http://example.com/publisher/45/1', $result['object']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/repository/45">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Catalog"/>
    <dcat:accessURL rdf:resource="http://example.com/access-url/45"/>
    <dct:title xml:lang="en">Repository 45</dct:title>
    <dct:publisher rdf:resource="http://example.com/publisher/45/1"/>
    <dct:publisher rdf:resource="http://example.com/publisher/45/2"/>
    <dct:publisher rdf:resource="http://example.com/publisher/45/3"/>
</rdf:Description>
RDF;
  }

  /**
   * {@inheritdoc}
   */
  protected function processGraphCallback(string $graph, ?string $subject, string $predicate, array $entity): void {
    // Deal only with asset repositories...
    if ($subject && $entity && $entity['type'] === static::ASSET_CATALOG) {
      // ...that have more than one publisher.
      if (isset($entity[$predicate]) && count($entity[$predicate]) > 1) {
        // Deletes the additional publishers from an asset repository.
        $publishers_to_delete = $entity[$predicate];
        // Extract only the additional publishers and build the query condition.
        array_shift($publishers_to_delete);
        $this->deleteTriples($graph, $subject, $predicate, SparqlArg::toResourceUris($publishers_to_delete));
      }
    }
  }

}

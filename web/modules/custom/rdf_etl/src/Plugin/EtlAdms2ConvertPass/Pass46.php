<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlAdms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginBase;

/**
 * Conversion Pass #46.
 *
 * URI: foaf:homepage
 * Type: Recommended property (Asset Repository)
 * Action: Updated
 * Description:
 * - Updated: URI: dcat:assetURL -> foaf:homepage. It is the web page that gives
 *   access to the Repository.
 * - Range: foaf:Documentation -> foaf:Document. This was an error in the
 *   revision draft ADMS-AP v0.08.
 *
 * @see https://joinup.ec.europa.eu/discussion/cr5-repository-modify-cardinality-property-dcataccessurl-01
 *
 * @Adms2ConvertPass(
 *   id = "pass_46",
 * )
 */
class Pass46 extends EtlAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $this->processGraph($data['sync_graph'], 'http://www.w3.org/ns/dcat#accessURL');
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/repository/46',
      'http://xmlns.com/foaf/spec/#term_homepage'
    );

    // Check that access URL cardinality is 1..1.
    $test->assertCount(1, $results);
    // Check that the first URL has been picked-up.
    $result = reset($results);
    $test->assertEquals('http://example.com/access-url/46/1', $result['object']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/repository/46">
   <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Catalog"/>
   <dcat:accessURL rdf:resource="http://example.com/access-url/46/1"/>
   <dcat:accessURL rdf:resource="http://example.com/access-url/46/2"/>
   <dcat:accessURL rdf:resource="http://example.com/access-url/46/3"/>
   <dct:title xml:lang="en">Repository 46</dct:title>
</rdf:Description>
RDF;
  }

  /**
   * {@inheritdoc}
   */
  protected function processGraphCallback(string $graph, ?string $subject, string $predicate, array $entity): void {
    // Deal only with asset repositories...
    if ($subject && $entity && $entity['type'] === static::ASSET_CATALOG) {
      // ...that have one or more than one access URLs.
      if (isset($entity[$predicate])) {
        $uris_to_delete = $entity[$predicate];
        // Deletes the additional access URIs as the news field is 0..1.
        $uri = $entity[$predicate][0];
        // Delete existing triples.
        $this->deleteTriples($graph, $subject, $predicate, SparqlArg::toResourceUris($uris_to_delete));
        $triples = [
          "<$subject>" => [
            "<http://xmlns.com/foaf/spec/#term_homepage>" => [
              "<$uri>",
            ],
          ],
        ];
        $this->insertTriples($graph, $triples);
      }
    }
  }

}

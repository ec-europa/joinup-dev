<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin\EtlAdms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginBase;

/**
 * Conversion Pass #37.
 *
 * URI: dcat:downloadURL
 * Type: N.A.
 * Action: Deleted
 * Description:
 * - Deleted: The property was deleted from the Asset Distribution class.
 *
 * @see https://joinup.ec.europa.eu/discussion/cr26-distribution-remove-property-dcatdownloadurl
 *
 * @Adms2ConvertPass(
 *   id = "pass_37",
 * )
 */
class Pass37 extends EtlAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $this->processGraph($data['sync_graph'], 'http://www.w3.org/ns/dcat#downloadURL');
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/distribution/37/1',
      'http://www.w3.org/ns/dcat#downloadURL'
    );
    // Check that the download URLs were removed from the 1st distribution.
    $test->assertEmpty($results);

    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/distribution/37/1',
      'http://www.w3.org/ns/dcat#accessURL'
    );
    // Check that the access URL value of the 1sr distribution was preserved.
    $test->assertEquals('http://example.com/access-url/37/1', $results[0]['object']);

    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/distribution/37/2',
      'http://www.w3.org/ns/dcat#downloadURL'
    );
    // Check that the download URLs were removed from the 2nd distribution.
    $test->assertEmpty($results);

    $results = $this->getTriplesFromGraph(
      static::TEST_GRAPH,
      'http://example.com/distribution/37/2',
      'http://www.w3.org/ns/dcat#accessURL'
    );
    // Check that download URLs were converted to access URLs.
    $test->assertCount(2, $results);
    $urls = array_column($results, 'object');
    sort($urls);
    $test->assertSame([
      'http://example.com/download-url/37/2/1',
      'http://example.com/download-url/37/2/2',
    ], $urls);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/distribution/37/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:accessURL rdf:resource="http://example.com/access-url/37/1"/>
    <dcat:downloadURL rdf:resource="http://example.com/download-url/37/1/1"/>
    <dcat:downloadURL rdf:resource="http://example.com/download-url/37/1/2"/>
    <dct:title xml:lang="en">Distribution 37/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/distribution/37/2">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:downloadURL rdf:resource="http://example.com/download-url/37/2/1"/>
    <dcat:downloadURL rdf:resource="http://example.com/download-url/37/2/2"/>
    <dct:title xml:lang="en">Distribution 37/2</dct:title>
</rdf:Description>
RDF;
  }

  /**
   * {@inheritdoc}
   */
  protected function processGraphCallback(string $graph, ?string $subject, string $predicate, array $entity): void {
    // Deal only with asset distributions...
    if ($subject && $entity && $entity['type'] === static::ASSET_DISTRIBUTION) {
      // ...that have one or more than one download URLs.
      if (isset($entity[$predicate])) {
        // Deletes the download URLs from the asset distribution.
        $download_urls_to_delete = SparqlArg::toResourceUris($entity[$predicate]);
        $this->deleteTriples($graph, $subject, $predicate, $download_urls_to_delete);

        // If this entity lacks access URLs, use download URLs as access URLs as
        // they are the same thing.
        // @see https://joinup.ec.europa.eu/discussion/cr26-distribution-remove-property-dcatdownloadurl
        if (!$this->getTriplesFromGraph($graph, $subject, 'http://www.w3.org/ns/dcat#accessURL')) {
          $this->insertTriples($graph, [
            SparqlArg::uri($subject) => [
              '<http://www.w3.org/ns/dcat#accessURL>' => $download_urls_to_delete,
            ],
          ]);
        }
      }
    }
  }

}

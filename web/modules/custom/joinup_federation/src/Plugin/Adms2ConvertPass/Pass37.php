<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

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
class Pass37 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $this->processGraph($data['sink_graph'], 'http://www.w3.org/ns/dcat#downloadURL');
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingSinkGraph(),
      'http://example.com/distribution/37/1',
      'http://www.w3.org/ns/dcat#downloadURL'
    );
    // Check that the download URLs were removed from the distribution.
    $test->assertEmpty($results);

    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingSinkGraph(),
      'http://example.com/distribution/37/1',
      'http://www.w3.org/ns/dcat#accessURL'
    );
    // Check that the access URL value of the distribution has been preserved.
    $test->assertEquals('http://example.com/access-url/37/1', $results[0]['object']);
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
      }
    }
  }

}

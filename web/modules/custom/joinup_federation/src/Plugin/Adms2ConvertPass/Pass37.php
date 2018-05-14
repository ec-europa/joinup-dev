<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #37.
 *
 * URI: dcat:downloadURL
 * Type: N.A.
 * Action: Deleted
 * Description:
 * - Deleted: The property was deleted from the Asset Distribution class because
 * it was sharing the same purpose with the dcat:accessURL property.
 * - Ensure that the value of the dcat:downloadURL exists as a accessURL before
 * removing the value.
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
    $sink_graph = $data['sink_graph'];
    // The FILTER NOT EXISTS avoids adding duplicate values.
    $insert_query = <<<QUERY
      WITH <$sink_graph>
      DELETE { ?subject <http://www.w3.org/ns/dcat#downloadURL> ?download_url }
      INSERT { ?subject <http://www.w3.org/ns/dcat#accessURL> ?download_url }
      WHERE {
        ?subject a ?type .
        ?subject <http://www.w3.org/ns/dcat#downloadURL> ?download_url .
        FILTER NOT EXISTS { ?subject <http://www.w3.org/ns/dcat#accessURL> ?download_url } .
        VALUES ?type { <http://www.w3.org/ns/adms#AssetDistribution> <http://www.w3.org/ns/dcat#Distribution> } .
      }
QUERY;

    // The delete query is separated so that values that are not converted to
    // access url are also cleaned up.
    $delete_query = <<<QUERY
      WITH <$sink_graph>
      DELETE { ?subject <http://www.w3.org/ns/dcat#downloadURL> ?value }
      WHERE {
        ?subject a ?type .
        ?subject <http://www.w3.org/ns/dcat#downloadURL> ?value .
        VALUES ?type { <http://www.w3.org/ns/adms#AssetDistribution> <http://www.w3.org/ns/dcat#Distribution> }  
      }
QUERY;

    $this->sparql->query($insert_query);
    $this->sparql->query($delete_query);
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    // Assert that there are no entities with a dcat:downloadURL property after
    // the conversion.
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/ns/dcat#downloadURL'
    );
    $test->assertEmpty($results);

    // Assert that the dcat:downloadURL property is properly converted as
    // dcat:accessURL when there is not a accessURL set already.
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      'http://example.com/distribution/37/1',
      'http://www.w3.org/ns/dcat#accessURL'
    );
    $test->assertCount(2, $results);
    // Check that the access URL value of the distribution has been preserved.
    $test->assertEquals('http://example.com/accessURL/37/1/1', $results[0]['object']);
    $test->assertEquals('http://example.com/accessURL/37/1/2', $results[1]['object']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/distribution/37/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:accessURL rdf:resource="http://example.com/accessURL/37/1/1"/>
    <dcat:downloadURL rdf:resource="http://example.com/accessURL/37/1/1"/>
    <dcat:downloadURL rdf:resource="http://example.com/accessURL/37/1/2"/>
    <dct:title xml:lang="en">Distribution 37/1</dct:title>
</rdf:Description>
RDF;
  }

}

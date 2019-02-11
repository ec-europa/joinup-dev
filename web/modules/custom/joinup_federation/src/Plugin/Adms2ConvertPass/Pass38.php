<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #38.
 *
 * URI: dcat:mediaType
 * Type: N.A.
 * Action: Deleted
 * Description:
 * - Deleted: The property was deleted from the Asset Distribution class because
 *   it was sharing the same purpose with the dcat:format property.
 * - Ensure that the value of the dcat:mediaType exists as a format before
 *   removing the value. The property dcat:format is optional with a maximum
 *   cardinality of 1 so it has to be taken into account in order when
 *   attempting to move the dcat:mediaType value.
 *
 * @Adms2ConvertPass(
 *   id = "pass_38",
 * )
 */
class Pass38 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $sink_graph = $data['sink_graph'];
    // The FILTER NOT EXISTS avoids adding duplicate values.
    $insert_query = <<<QUERY
      WITH <$sink_graph> 
      DELETE { ?subject <http://www.w3.org/ns/dcat#mediaType> ?media_type }
      INSERT { ?subject <http://www.w3.org/ns/dcat#format> ?media_type }
      WHERE {
        ?subject a ?type .
        ?subject <http://www.w3.org/ns/dcat#mediaType> ?media_type .
        FILTER NOT EXISTS { ?subject <http://www.w3.org/ns/dcat#format> ?media_type } .
        VALUES ?type { <http://www.w3.org/ns/adms#AssetDistribution> <http://www.w3.org/ns/dcat#Distribution> } .  
      }
      GROUP BY ?subject ?media_type
      HAVING (COUNT(?media_type) = 1)
QUERY;

    // The delete query is separated so that values that are not converted to
    // format are also cleaned up.
    $delete_query = <<<QUERY
      WITH <$sink_graph>
      DELETE { ?subject <http://www.w3.org/ns/dcat#mediaType> ?value }
      WHERE {
        ?subject a ?type .
        ?subject <http://www.w3.org/ns/dcat#mediaType> ?value .
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
    // Assert that there are no entities with a dcat:mediaType property after
    // the conversion.
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/ns/dcat#mediaType'
    );
    $test->assertEmpty($results);

    // Assert that the dcat:mediaType property is properly converted as
    // dcat:format when there is not a format set already.
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      'http://example.com/distribution/38/1',
      'http://www.w3.org/ns/dcat#format'
    );
    // Check that the access URL value of the distribution has been preserved.
    $test->assertEquals('http://example.com/format/38/1/1', $results[0]['object']);

    // Assert that the dcat:mediaType property does not persist over the
    // dcat:format property if the later is already set.
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      'http://example.com/distribution/38/2',
      'http://www.w3.org/ns/dcat#format'
    );
    // Check that the access URL value of the distribution has been preserved.
    $test->assertEquals('http://example.com/format/38/2/1', $results[0]['object']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    // The entity 38-1 will have the property mediaType converted to the
    // dcat:format property. Entity 38-2 will simply have the property
    // dcat:mediaType removed as there is already a format set.
    return <<<RDF
<rdf:Description rdf:about="http://example.com/distribution/38/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:mediaType rdf:resource="http://example.com/format/38/1/1"/>
    <dct:title xml:lang="en">Distribution 38/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/distribution/38/2">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:format rdf:resource="http://example.com/format/38/2/1"/>
    <dcat:mediaType rdf:resource="http://example.com/format/38/2/2"/>
    <dct:title xml:lang="en">Distribution 38/2</dct:title>
</rdf:Description>
RDF;
  }

}

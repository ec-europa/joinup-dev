<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #4.
 *
 * URI: http://purl.org/dc/terms/publisher
 * Type: Mandatory class
 * Action: Updated
 * Description:
 * - Updated: The class Publisher was replaced by definition by the class Agent,
 *   as it covers the only agent role in the profile.
 *
 * @Adms2ConvertPass(
 *   id = "pass_11",
 * )
 */
class Pass11 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    // The following query will have the owner type change its type in owners
    // found in classes with both the old versions of the definitions of
    // collections, solutions, releases and distributions to avoid conflicts
    // with other passes.
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?owner <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://purl.org/dc/terms/publisher> }
INSERT { ?owner <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://xmlns.com/foaf/0.1/Agent> }
WHERE { 
  ?subject a ?subject_type .
  ?subject <http://purl.org/dc/terms/publisher> ?owner .
  ?owner a <http://purl.org/dc/terms/publisher> .
  VALUES ?subject_type { <http://www.w3.org/ns/adms#Asset> <http://www.w3.org/ns/adms#AssetRepository> <http://www.w3.org/ns/adms#AssetDistribution> <http://www.w3.org/ns/dcat#Dataset> <http://www.w3.org/ns/dcat#Catalog> <http://www.w3.org/ns/dcat#Distribution> }
}
QUERY;

    $this->sparql->query($query);
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      '<http://purl.org/dc/terms/publisher>'
    );
    $test->assertEmpty($results);

    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      '<http://xmlns.com/foaf/0.1/Agent>'
    );
    // Exactly 3 owners exist with the type <http://xmlns.com/foaf/0.1/Agent>.
    $test->assertCount(3, $results[0]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/dataset/11/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Dataset"/>
    <dct:publisher rdf:resource="http://example.com/owner/11/1"/>
    <dct:title xml:lang="en">Dataset 11/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/assetRepository/11/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#AssetRepository"/>
    <dct:publisher rdf:resource="http://example.com/owner/11/1"/>
    <dct:title xml:lang="en">Dataset 11/2</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/distribution/11/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dct:publisher rdf:resource="http://example.com/owner/11/2"/>
    <dct:title xml:lang="en">Distribution 11/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/11/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:publisher rdf:resource="http://example.com/owner/11/3"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/owner/11/1">
    <rdf:type rdf:resource="http://purl.org/dc/terms/publisher"/>
    <dct:title xml:lang="en">Owner 11/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/owner/11/2">
    <rdf:type rdf:resource="http://purl.org/dc/terms/publisher"/>
    <dct:title xml:lang="en">Owner 11/2</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/owner/11/3">
    <rdf:type rdf:resource="http://purl.org/dc/terms/publisher"/>
    <dct:title xml:lang="en">Owner 11/3</dct:title>
</rdf:Description>
RDF;
  }

}

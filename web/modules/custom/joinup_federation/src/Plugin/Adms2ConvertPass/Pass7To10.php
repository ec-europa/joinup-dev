<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #7, #8, #9, #10.
 *
 * URI: admssw:SoftwarePackage, admssw:SoftwareProject, admssw:SoftwareRelease,
 * admssw:SoftwareRepository
 * Type: N.A.
 * Action: Deleted
 * Description:
 *  - The optional classes above were deleted and now all of them are of type
 *  dcat:Dataset.
 *
 * @Adms2ConvertPass(
 *   id = "pass_7_8_9_10",
 *   weight = -1
 * )
 */
class Pass7To10 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?subject a ?type }
INSERT { ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/ns/dcat#Dataset> } 
WHERE { 
  ?subject a ?type . 
  VALUES ?type { <http://purl.org/adms/sw/SoftwarePackage> <http://purl.org/adms/sw/SoftwareProject> <http://purl.org/adms/sw/SoftwareRelease> <http://purl.org/adms/sw/SoftwareRepository> } 
}
QUERY;
    $this->sparql->query($query);
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $ids = [
      'http://example.com/dataset/7/1',
      'http://example.com/dataset/8/1',
      'http://example.com/dataset/9/1',
      'http://example.com/dataset/10/1',
    ];

    foreach ($ids as $id) {
      $results = $this->getTriplesFromGraph(
        ConvertToAdms2StepTest::getTestingGraphs()['sink'],
        $id,
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
      );

      $test->assertEquals('http://www.w3.org/ns/dcat#Dataset', $results[0]['object']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/dataset/7/1">
    <rdf:type rdf:resource="http://purl.org/adms/sw/SoftwarePackage"/>
    <dct:title xml:lang="en">Dataset 7/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/dataset/8/1">
    <rdf:type rdf:resource="http://purl.org/adms/sw/SoftwarePackage"/>
    <dct:title xml:lang="en">Dataset 8/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/dataset/9/1">
    <rdf:type rdf:resource="http://purl.org/adms/sw/SoftwarePackage"/>
    <dct:title xml:lang="en">Dataset 9/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/dataset/10/1">
    <rdf:type rdf:resource="http://purl.org/adms/sw/SoftwarePackage"/>
    <dct:title xml:lang="en">Dataset 10/1</dct:title>
</rdf:Description>
RDF;
  }

}

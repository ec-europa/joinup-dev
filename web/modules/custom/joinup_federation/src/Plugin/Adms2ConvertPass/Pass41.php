<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #41.
 *
 * URI: dct:issued
 * Type: N.A.
 * Action: Updated
 * Description:
 * - The cardinality changed to single valued. Only the first value will be
 * kept.
 *
 * @Adms2ConvertPass(
 *   id = "pass_41",
 * )
 */
class Pass41 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $results = $this->getTriplesFromGraph($data['sink_graph'],
      NULL,
      'http://www.w3.org/ns/dcat#issued'
    );

    $entities = [];
    // In order to be fair, and since the notion of deltas does not exist in
    // SPARQL, we are only going to keep the first instance found for the given
    // value.
    foreach ($results as $result) {
      if (isset($entities[$result['subject']])) {
        continue;
      }
      $entities[$result['subject']] = $result['object'];
    }

    foreach ($entities as $subject => $object) {
      $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { <$subject> <http://www.w3.org/ns/dcat#issued> ?issued }
INSERT { <$subject> <http://www.w3.org/ns/dcat#issued> "$object"^^<http://www.w3.org/2001/XMLSchema#dateTime> }
WHERE {
        <$subject> a ?type .
        <$subject> <http://www.w3.org/ns/dcat#issued> ?issued .
        VALUES ?type { <http://www.w3.org/ns/adms#AssetDistribution> <http://www.w3.org/ns/dcat#Distribution> }
      }
QUERY;

      $this->sparql->query($query);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $entities = [
      'http://example.com/dataset/41/1',
      'http://example.com/dataset/41/2',
    ];
    foreach ($entities as $entity) {
      $results = $this->getTriplesFromGraph(
        ConvertToAdms2StepTest::getTestingGraphs()['sink'],
        $entity,
        'http://www.w3.org/ns/dcat#issued'
      );
      $test->assertCount(1, $results);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/dataset/41/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2018-05-01T00:00:00</dcat:issued>
    <dcat:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2018-05-02T00:00:00</dcat:issued>
    <dcat:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2018-05-01T00:00:04</dcat:issued>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/dataset/41/2">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Distribution"/>
    <dcat:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2018-05-02T00:00:00</dcat:issued>
    <dcat:issued rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">2018-05-03T00:00:04</dcat:issued>
</rdf:Description>
RDF;
  }

}

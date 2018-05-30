<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #45.
 *
 * URI: dct:publisher
 * Type: N.A.
 * Action: Updated
 * Description:
 * - Updated: Cardinality: 1..n -> 1..1.
 * - Updated the definition: the publisher is the Agent that publishes the asset
 *   or solutions, not the Agent that publishes the metadata about it.
 *
 * @Adms2ConvertPass(
 *   id = "pass_45",
 * )
 */
class Pass45 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $results = $this->getTriplesFromGraph($data['sink_graph'],
      NULL,
      'http://www.w3.org/ns/dcat#publisher'
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
DELETE { <$subject> <http://www.w3.org/ns/dcat#publisher> ?publisher }
INSERT { <$subject> <http://www.w3.org/ns/dcat#publisher> <$object> }
WHERE {
        <$subject> a ?type .
        <$subject> <http://www.w3.org/ns/dcat#publisher> ?publisher .
        VALUES ?type { <http://www.w3.org/ns/adms#AssetRepository> <http://www.w3.org/ns/dcat#Catalog> }
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
      'http://example.com/catalog/45/1',
      'http://example.com/catalog/45/2',
    ];
    foreach ($entities as $entity) {
      $results = $this->getTriplesFromGraph(
        ConvertToAdms2StepTest::getTestingGraphs()['sink'],
        $entity,
        'http://www.w3.org/ns/dcat#publisher'
      );
      $test->assertCount(1, $results);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/catalog/45/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#AssetRepository"/>
    <dcat:publisher rdf:resource="http://example.com/publisher/45/1"/>
    <dcat:publisher rdf:resource="http://example.com/publisher/45/2"/>
    <dcat:publisher rdf:resource="http://example.com/publisher/45/3"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/catalog/45/2">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Catalog"/>
    <dcat:publisher rdf:resource="http://example.com/publisher/45/4"/>
    <dcat:publisher rdf:resource="http://example.com/publisher/45/2"/>
    <dcat:publisher rdf:resource="http://example.com/publisher/45/5"/>
</rdf:Description>
RDF;
  }

}

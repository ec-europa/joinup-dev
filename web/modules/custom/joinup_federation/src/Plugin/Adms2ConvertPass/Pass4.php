<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #4.
 *
 * URI: qb:Dataset (http://purl.org/linked-data/cube#Dataset)
 * Type: N.A.
 * Action: Deleted
 * Description:
 * - Deleted: The property was removed entirely.
 *
 * @Adms2ConvertPass(
 *   id = "pass_4",
 * )
 */
class Pass4 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?subject a <http://purl.org/linked-data/cube#Dataset> }
WHERE { ?subject a <http://purl.org/linked-data/cube#Dataset> }
QUERY;

    $this->sparql->query($query);
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      'http://example.com/dataset/4/1',
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      '<http://purl.org/linked-data/cube#Dataset>'
    );
    // Check that the qb:Dataset object was deleted.
    $test->assertEmpty($results);

    // Ensure that the type property was not deleted.
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      'http://example.com/dataset/4/1',
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      '<http://www.w3.org/ns/dcat#Dataset>'
    );
    $test->assertEquals('http://example.com/dataset/4/1', $results[0]['subject']);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    // The qb:Dataset property was set as a class type rather than a predicate.
    // In the examples, the class usually had 2 types, one of which was the
    // qb:Dataset, and the other one was dcat:Dataset.
    return <<<RDF
<rdf:Description rdf:about="http://example.com/dataset/4/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Dataset"/>
    <rdf:type rdf:resource="http://purl.org/linked-data/cube#Dataset"/>
    <dct:title xml:lang="en">Dataset 4/1</dct:title>
</rdf:Description>
RDF;
  }

}

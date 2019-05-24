<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Passes #1, #2 and #3.
 *
 * Pass: #1
 * URI: dcat:Dataset
 * Type: Mandatory class
 * Action: Updated
 * Description:
 * - Updated: An asset was declared as dcat:Dataset and not adms:Asset.
 *
 * Pass: #2
 * URI: dcat:Distribution,
 * Type: Recommended class
 * Action: Updated
 * Description:
 * - Updated: An asset distribution was declared as dcat:Distribution rather
 *   than adms:AssetDistribution.
 * - Removed statement about backwards compatibility.
 *
 * Pass: #3
 * URI: dcat:Catalog
 * Type: Optional class
 * Action: Updated
 * Description:
 * - Updated: A catalogue of assets was declared as dcat:Catalog and not
 *   adms:AssetRepository.
 * - Removed statement about backwards compatibility.
 *
 * @see https://joinup.ec.europa.eu/discussion/cr42-make-adms-ap-dcat-ap
 *
 * @Adms2ConvertPass(
 *   id = "pass_1_2_3",
 *   weight = -1000
 * )
 */
class Pass1To3 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $graph = $data['sink_graph'];
    $objects = SparqlArg::serializeUris(array_keys(static::getAdms1To2TypeConversionMap()), ' ');
    $results = $this->getTriplesFromGraph(
      $graph,
      NULL,
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      $objects
    );
    if (!$results) {
      return;
    }

    do {
      // Avoid building huge queries.
      $processing_results = array_splice($results, 0, 100);
      $triples = [];
      foreach ($processing_results as $triple) {
        $triples[SparqlArg::uri($triple['subject'])][SparqlArg::uri($triple['predicate'])][] = SparqlArg::uri(static::getAdms1To2TypeConversionMap($triple['object']));
        $this->deleteTriples($graph, $triple['subject'], $triple['predicate'], ["<{$triple['object']}>"]);
      }
      $this->insertTriples($graph, $triples);
    } while ($results);
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $entities = [
      static::ASSET => 'http://example.com/asset/1_2_3',
      static::ASSET_CATALOG => 'http://example.com/repository/1_2_3',
      static::ASSET_DISTRIBUTION => 'http://example.com/distribution/1_2_3',
    ];

    foreach (static::getAdms1To2TypeConversionMap() as $adms1_uri => $adms2_uri) {
      $results = $this->getTriplesFromGraph(
        ConvertToAdms2StepTest::getTestingGraphs()['sink'],
        $entities[$adms2_uri],
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        "<$adms2_uri>"
      );
      // Check that the type has been changed.
      $test->assertCount(1, $results);

      $results = $this->getTriplesFromGraph(
        ConvertToAdms2StepTest::getTestingGraphs()['sink'],
        $entities[$adms2_uri],
        'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
        "<$adms1_uri>"
      );
      // Check that the old type triple has been removed.
      $test->assertEmpty($results);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/asset/1_2_3">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 1 2 3</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/repository/1_2_3">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#AssetRepository"/>
    <dct:title xml:lang="en">Repository 1 2 3</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/distribution/1_2_3">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#AssetDistribution"/>
    <dct:title xml:lang="en">Distribution 1 2 3</dct:title>
</rdf:Description>
RDF;
  }

  /**
   * Gets the type conversion map.
   *
   * @param string|null $adms1_uri
   *   (optional) The ADMS v1 type URI.
   *
   * @return string|string[]
   *   If $adms1_uri has been passed will return the corresponding ADMS v2 URI,
   *   otherwise an associative array of ADMS v2 type URIs keyed by ADMSv1 type
   *   URIs.
   */
  protected static function getAdms1To2TypeConversionMap(string $adms1_uri = NULL) {
    $conversion_map = [
      'http://www.w3.org/ns/adms#Asset' => static::ASSET,
      'http://www.w3.org/ns/adms#AssetRepository' => static::ASSET_CATALOG,
      'http://www.w3.org/ns/adms#AssetDistribution' => static::ASSET_DISTRIBUTION,
    ];
    return $adms1_uri ? $conversion_map[$adms1_uri] : $conversion_map;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #50.
 *
 * URI: adms:supportedSchema
 * Type: N.A.
 * Action: Deleted
 * Description:
 * - Deleted: The property was deleted from the Asset Repository class. It could
 *   be described as an asset. The ADMS-AP schemas can be published and
 *   described by Joinup.
 *
 * @see https://joinup.ec.europa.eu/discussion/cr3-repository-remove-property-admssupportedschema-or-change-property-range
 *
 * @Adms2ConvertPass(
 *   id = "pass_50",
 * )
 */
class Pass50 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $this->processGraph($data['sink_graph'], 'http://www.w3.org/ns/adms#supportedSchema');
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingSinkGraph(),
      'http://example.com/repository/50',
      'http://www.w3.org/ns/adms#supportedSchema'
    );
    // Check that triples were removed.
    $test->assertEmpty($results);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/repository/50">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Catalog"/>
    <dct:title xml:lang="en">Repository 50</dct:title>
    <adms:supportedSchema xml:lang="en">1.01</adms:supportedSchema>
    <adms:supportedSchema xml:lang="en">1.02</adms:supportedSchema>
</rdf:Description>
RDF;
  }

  /**
   * {@inheritdoc}
   */
  protected function processGraphCallback(string $graph, ?string $subject, string $predicate, array $entity): void {
    // Deal only with asset repositories...
    if ($subject && $entity && $entity['type'] === static::ASSET_CATALOG) {
      // ...that have one or more than one supported schemas.
      if (!empty($entity[$predicate])) {
        // Deletes the supported schemas from the asset repository.
        $this->deleteTriples($graph, $subject, $predicate);
      }
    }
  }

}

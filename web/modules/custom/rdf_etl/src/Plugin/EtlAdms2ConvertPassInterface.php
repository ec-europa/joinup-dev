<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\KernelTests\KernelTestBase;

/**
 * Interface for ADMS v2 conversion pass plugin.
 *
 * Such a plugin performs only one specific change/pass in the process of
 * converting to ADMS v1 data to ADMS v2.01.
 */
interface EtlAdms2ConvertPassInterface {

  /**
   * Testing graph.
   *
   * @var string
   */
  const TEST_GRAPH = 'http://example.com/graph/sync_test';

  /**
   * Transforms the triples in the backend.
   */
  public function convert(): void;

  /**
   * Performs testing assertions.
   *
   * @param \Drupal\KernelTests\KernelTestBase $test
   *   The testing class.
   */
  public function performAssertions(KernelTestBase $test): void;

  /**
   * Returns testing data for this conversion pass.
   *
   * @return string|null
   *   The RDF data as markup.
   */
  public function getTestingRdfData(): ?string;

}

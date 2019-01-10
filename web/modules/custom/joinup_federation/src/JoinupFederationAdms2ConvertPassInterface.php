<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation;

use Drupal\KernelTests\KernelTestBase;

/**
 * Interface for ADMS v2 conversion pass plugin.
 *
 * Such a plugin performs only one specific change/pass in the process of
 * converting to ADMS v1 data to ADMS v2.01.
 */
interface JoinupFederationAdms2ConvertPassInterface {

  /**
   * The ADMS v2 asset type.
   *
   * @var string
   */
  const ASSET = 'http://www.w3.org/ns/dcat#Dataset';

  /**
   * The ADMS v2 catalog of assets type.
   *
   * @var string
   */
  const ASSET_CATALOG = 'http://www.w3.org/ns/dcat#Catalog';
  /**
   * The ADMS v2 asset distribution type.
   *
   * @var string
   */
  const ASSET_DISTRIBUTION = 'http://www.w3.org/ns/dcat#Distribution';

  /**
   * Transforms the triples in the backend.
   *
   * @param array $data
   *   Data received from the pipeline step plugin.
   */
  public function convert(array $data): void;

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

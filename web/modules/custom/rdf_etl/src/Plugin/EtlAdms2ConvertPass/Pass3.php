<?php

namespace Drupal\rdf_etl\Plugin\EtlAdms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_etl\Plugin\EtlAdms2ConvertPassPluginBase;

/**
 * Conversion Pass #3.
 *
 * URI: dcat:Catalog
 * Type: Optional class
 * Action: Updated
 * Description:
 * - Updated: A catalogue of assets was declared as dcat:Catalog and not
 *   adms:AssetRepository. Removed statement about backwards compatibility.
 * Change requests: CR42
 *
 * @Adms2ConvertPass(
 *   id = "pass_3",
 *   weight = 10,
 * )
 */
class Pass3 extends EtlAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(): void {
    // Implement here the transformation needed to fix the change #3.
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    // Add assertions.
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    // Add testing data for change #3.
    return NULL;
  }

}

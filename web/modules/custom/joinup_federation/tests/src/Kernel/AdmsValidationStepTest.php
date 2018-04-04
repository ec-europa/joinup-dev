<?php

namespace Drupal\Tests\joinup_federation\Kernel;

use EasyRdf\Graph;

/**
 * Tests the 'adms_validation' pipeline step plugin.
 *
 * @group joinup_federation
 */
class AdmsValidationStepTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['adms_validator'];

  /**
   * Tests the ADMS validation step.
   *
   * @param string $rdf_file
   *   The RDF file to be tested.
   * @param bool $expected_valid
   *   Expectancy: The file is a valid ADMS v2 file.
   *
   * @throws \Exception
   *   If the plugin is invalid.
   *
   * @dataProvider providerTestAdmsValidationStepPlugin
   */
  public function testAdmsValidationStepPlugin(string $rdf_file, bool $expected_valid): void {
    $graph = new Graph();
    $graph->parseFile(__DIR__ . "/../../fixtures/$rdf_file");
    $this->createGraphStore()->replace($graph, static::getTestingSinkGraph());

    $result = $this->runPipelineStep('adms_validation');

    if ($expected_valid) {
      // Check that no error was detected during validation.
      $this->assertEmpty($result);
    }
    else {
      // Check that errors were detected during validation.
      $this->assertNotEmpty($result);
    }
  }

  /**
   * Provides testing cases for testAdmsValidationStepPlugin.
   *
   * @return array[]
   *   A list of testing cases. See ::testAdmsValidationStepPlugin() signature
   *   for the structure of each array element in the list.
   *
   * @see self::testAdmsValidationStepPlugin()
   */
  public function providerTestAdmsValidationStepPlugin(): array {
    return [
      'ADMSv2 non-compliant' => ['invalid_adms.rdf', FALSE],
      'ADMSv2 compliant' => ['valid_adms.rdf', TRUE],
    ];
  }

}

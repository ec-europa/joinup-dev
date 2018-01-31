<?php

namespace Drupal\Tests\rdf_etl\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use EasyRdf\Graph;

/**
 * Tests the 'adms_validation' process step plugin.
 *
 * @group rdf_etl
 */
class AdmsValidationStepTest extends KernelTestBase {

  use RdfDatabaseConnectionTrait;
  use RdfEntityGraphStoreTrait;

  /**
   * The testing sink graph.
   *
   * @var string
   */
  const TEST_GRAPH = 'http://example.com/graph/test/sink';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'adms_validator',
    'rdf_entity',
    'rdf_etl',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();
    // Make sure that the SPARQL testing graph is empty before running any test.
    $this->sparql->query("CLEAR GRAPH <" . static::TEST_GRAPH . ">;");
  }

  /**
   * Tests the ADMS validation step.
   *
   * @param string $rdf_file
   *   The RDF file to be tested.
   * @param bool $valid
   *   Expectancy: The file is a valid ADMS v2 file.
   *
   * @dataProvider providerTestAdmsValidationStepPlugin
   */
  public function testAdmsValidationStepPlugin(string $rdf_file, bool $valid): void {
    /** @var \Drupal\rdf_etl\Plugin\EtlProcessStepManager $manager */
    $manager = \Drupal::service('plugin.manager.etl_process_step');
    $data = ['sink_graph' => static::TEST_GRAPH];
    $plugin = $manager->createInstance('adms_validation', $data);

    $graph = new Graph();
    $graph->parseFile(__DIR__ . "/../../fixtures/$rdf_file");
    $this->createGraphStore()->replace($graph, static::TEST_GRAPH);
    $plugin->execute($data);

    if ($valid) {
      // Check that no error was detected during validation.
      $this->assertArrayNotHasKey('error', $data);
    }
    else {
      // Check that errors were detected during validation.
      $this->assertArrayHasKey('error', $data);
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

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->sparql->query("CLEAR GRAPH <" . static::TEST_GRAPH . ">;");
    parent::tearDown();
  }

}

<?php

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use EasyRdf\Graph;

/**
 * Tests the 'attach_provenance_data' process step plugin.
 *
 * @group rdf_etl
 */
class AttachProvenanceDataStepTest extends KernelTestBase {

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
    'pipeline',
    'joinup_federation',
    'joinup_federation_test',
    'rdf_entity',
    'user',
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
   * @throws \Exception
   *   If the plugin is invalid.
   */
  public function testAdmsValidationStepPlugin(): void {
    /** @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager */
    $pipeline_plugin_manager = $this->container->get('plugin.manager.pipeline_pipeline');
    $data = ['sink_graph' => static::TEST_GRAPH];
    /** @var \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline */
    $pipeline = $pipeline_plugin_manager->createInstance('joinup_federation_testing_pipeline', $data);
    $step = $pipeline->createStepInstance('attach_provenance_data');

    $graph = new Graph();
    $graph->parseFile(__DIR__ . '/../../fixtures/valid_adms.rdf');
    $this->createGraphStore()->replace($graph, static::TEST_GRAPH);

    // Execute the validation step.
    $step->execute($data);
    $this->assertNotEmpty($data['activities']);
    $this->assertEquals(3, count($data['activities']));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->sparql->query("CLEAR GRAPH <" . static::TEST_GRAPH . ">;");
    parent::tearDown();
  }

}

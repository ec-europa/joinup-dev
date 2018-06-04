<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\RdfEntityGraphStoreTrait;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Provides a base class for pipeline step kernel tests.
 */
abstract class StepTestBase extends KernelTestBase {

  use RdfDatabaseConnectionTrait;
  use RdfEntityGraphStoreTrait;

  /**
   * Testing pipeline.
   *
   * @var \Drupal\joinup_federation\JoinupFederationPipelineInterface
   */
  protected $pipeline;

  /**
   * Returns the tested pipeline steps plugins data.
   *
   * @return array[]
   *   An associative array of steps that are used in this test. The keys are
   *   pipeline step plugin IDs and the values are their configurations.
   */
  abstract protected function getUsedStepPlugins(): array;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'pipeline',
    'joinup_federation',
    'joinup_federation_test',
    'rdf_entity',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();

    /** @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager */
    $pipeline_plugin_manager = $this->container->get('plugin.manager.pipeline_pipeline');
    /** @var \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline */
    $this->pipeline = $pipeline_plugin_manager->createInstance('joinup_federation_testing_pipeline');
    $this->pipeline->setSteps($this->getUsedStepPlugins());
  }

  /**
   * Runs a given step and returns its results.
   *
   * @param string $step_plugin_id
   *   The pipeline step.
   *
   * @return array|null
   *   NULL on success, a render array on error.
   */
  protected function runPipelineStep(string $step_plugin_id) {
    $step_plugin_instance = $this->pipeline->createStepInstance($step_plugin_id);
    $data = [];
    return $step_plugin_instance->execute($data);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->pipeline->clearGraphs();
    parent::tearDown();
  }

  /**
   * Returns the testing graphs.
   *
   * @return array
   *   The testing sink graphs.
   */
  public static function getTestingGraphs(): array {
    return [
      'sink' => 'http://joinup-federation/sink',
      'sink_plus_taxo' => 'http://joinup-federation/sink-plus-taxo',
    ];
  }

}

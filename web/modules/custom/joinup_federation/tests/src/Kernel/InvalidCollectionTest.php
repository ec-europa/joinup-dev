<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\pipeline\Exception\PipelineStepPrepareLogicException;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\sparql_entity_storage\Entity\SparqlGraph;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;

/**
 * Tests the invalid pipeline collection URI..
 *
 * @group joinup_federation
 */
class InvalidCollectionTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getUsedStepPlugins(): array {
    // We need a sample step to run to ensure the exception.
    // 'add_joinup_vocabularies' is a randomly selected step.
    return ['add_joinup_vocabularies' => []];
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'joinup_sparql',
    'rdf_schema_field_validation',
    'rdf_taxonomy',
    'taxonomy',
    'text',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', ['key_value_expire']);
    $graph = Yaml::decode(file_get_contents(DRUPAL_ROOT . '/modules/contrib/sparql_entity_storage/config/install/sparql_entity_storage.graph.default.yml'));
    SparqlGraph::create($graph)->save();

    // Create the collection bundle.
    RdfEntityType::create(['rid' => 'collection', 'name' => 'Collection'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/sparql_entity_storage.mapping.rdf_entity.collection.yml'));
    SparqlMapping::create($mapping)->save();
  }

  /**
   * Sets up the pipeline with the joinup_federation_testing_pipeline plugin.
   */
  protected function setUpPipeline(): void {
    /** @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager */
    $pipeline_plugin_manager = $this->container->get('plugin.manager.pipeline_pipeline');
    /** @var \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline */
    $this->pipeline = $pipeline_plugin_manager->createInstance('joinup_federation_invalid_testing_pipeline');
    $this->pipeline->setSteps($this->getUsedStepPlugins());
  }

  /**
   * Test the invalid collection URI.
   */
  public function testInvalidCollectionUri(): void {
    $this->expectException(PipelineStepPrepareLogicException::class);
    $this->expectExceptionMessage("A collection with URI 'http://invalid-collection-id' does not exist.");
    $this->runPipelinePrepare('add_joinup_vocabularies');
  }

}

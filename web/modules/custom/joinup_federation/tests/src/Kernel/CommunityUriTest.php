<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Tests\joinup_test\Traits\ConfigTestTrait;
use Drupal\pipeline\Exception\PipelineStepPrepareLogicException;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * Tests the invalid pipeline community URI..
 *
 * @group joinup_federation
 */
class CommunityUriTest extends StepTestBase {

  use ConfigTestTrait;

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
  protected function getUsedStepPlugins(): array {
    // We need a sample step to run to ensure the exception.
    // 'add_joinup_vocabularies' is a randomly selected step.
    return ['add_joinup_vocabularies' => []];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['key_value_expire']);
    // Create the community bundle.
    RdfEntityType::create(['rid' => 'collection', 'name' => 'Community'])->save();
    // Create graph and mapping.
    $this->importConfigs([
      'sparql_entity_storage.graph.default',
      'sparql_entity_storage.mapping.rdf_entity.collection',
    ]);
  }

  /**
   * Sets up the pipeline with the joinup_federation_testing_pipeline plugin.
   */
  protected function setUpPipeline(): void {
    /** @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager $pipeline_plugin_manager */
    $pipeline_plugin_manager = $this->container->get('plugin.manager.pipeline_pipeline');
    /** @var \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline */
    $this->pipeline = $pipeline_plugin_manager->createInstance('joinup_federation_pipeline_collection_uri_test');
    $this->pipeline->setSteps($this->getUsedStepPlugins());
  }

  /**
   * Test the missed community URI.
   */
  public function testMissedCommunityUri(): void {
    $this->container->get('state')->set('joinup_federation.test.collection', 'missed');
    $error = strip_tags($this->pipeline->prepare()->__toString());
    $this->assertEquals('The Joinup federation pipeline community URI testing import pipeline is not linked to any community. Contact the site administrator.', $error);
  }

  /**
   * Test the invalid community URI.
   */
  public function testInvalidCommunityUri(): void {
    $this->container->get('state')->set('joinup_federation.test.collection', 'invalid');
    $this->expectException(PipelineStepPrepareLogicException::class);
    $this->expectExceptionMessage("A community with URI 'http://invalid-community-id' does not exist.");
    $this->runPipelinePrepare('add_joinup_vocabularies');
  }

  /**
   * Test the community URI declared in annotation.
   */
  public function testCommunityUriFromAnnotation(): void {
    $this->container->get('state')->set('joinup_federation.test.collection', 'from_annotation');
    $this->assertEquals('http://from-annotation', $this->pipeline->getCommunity());
  }

}

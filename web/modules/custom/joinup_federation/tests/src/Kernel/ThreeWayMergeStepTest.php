<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\pipeline\PipelineState;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityGraph;
use Drupal\rdf_entity\Entity\RdfEntityMapping;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\taxonomy\Entity\Vocabulary;
use EasyRdf\Graph;

/**
 * Tests the '3_way_merge' process step plugin.
 *
 * @group joinup_federation
 */
class ThreeWayMergeStepTest extends StepTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getUsedStepPlugins(): array {
    return [
      'remove_unsupported_data' => [],
      'add_joinup_vocabularies' => [],
      '3_way_merge' => [
        'collection' => 'http://catalog',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'rdf_schema_field_validation',
    'rdf_taxonomy',
    'taxonomy',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create the 'default' and 'staging' graphs.
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/joinup/config/install/rdf_entity.graph.default.yml'));
    RdfEntityGraph::create($graph)->save();
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../../../../profiles/joinup/config/install/rdf_entity.graph.draft.yml'));
    RdfEntityGraph::create($graph)->save();
    $graph = Yaml::decode(file_get_contents(__DIR__ . '/../../../config/install/rdf_entity.graph.staging.yml'));
    RdfEntityGraph::create($graph)->save();

    // Create the language vocabulary and mapping.
    Vocabulary::create(['vid' => 'language', 'name' => 'Language'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../joinup_core/config/install/rdf_entity.mapping.taxonomy_term.language.yml'));
    RdfEntityMapping::create($mapping)->save();

    // Create the solution RDF type.
    RdfEntityType::create(['rid' => 'solution', 'name' => 'Solution'])->save();
    // Add some fields for the purpose of this test.
    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'rdf_entity',
      'field_name' => 'field_is_description',
    ])->setThirdPartySetting('rdf_entity', 'mapping', [
      'value' => [
        'predicate' => 'http://purl.org/dc/terms/description',
        'format' => 't_literal',
      ],
      'format' => [
        'predicate' => '',
        'format' => '',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'rdf_entity',
      'bundle' => 'solution',
      'field_name' => 'field_is_description',
      'label' => 'Description',
    ])->save();
    FieldStorageConfig::create([
      'type' => 'entity_reference',
      'entity_type' => 'rdf_entity',
      'field_name' => 'field_status',
    ])->setThirdPartySetting('rdf_entity', 'mapping', [
      'target_id' => [
        'predicate' => 'http://www.w3.org/ns/adms#status',
        'format' => 'resource',
      ],
    ])->save();
    FieldConfig::create([
      'entity_type' => 'rdf_entity',
      'bundle' => 'solution',
      'field_name' => 'field_status',
      'label' => 'Status',
      'default_value' => [
        [
          'target_id' => 'http://example.com/default-status',
        ],
      ],
    ])->save();

    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../solution/config/install/rdf_entity.mapping.rdf_entity.solution.yml'));
    RdfEntityMapping::create($mapping)->save();

    // Create the collection bundle.
    RdfEntityType::create(['rid' => 'collection', 'name' => 'Collection'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/rdf_entity.mapping.rdf_entity.collection.yml'));
    RdfEntityMapping::create($mapping)->save();
    // And the affiliates field.
    $field_storage_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.storage.rdf_entity.field_ar_affiliates.yml'));
    FieldStorageConfig::create($field_storage_config)->save();
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.field.rdf_entity.collection.field_ar_affiliates.yml'));
    FieldConfig::create($field_config)->save();
  }

  /**
   * Test values assignment with an existing solution.
   */
  public function testExistingSolution() {
    // Create a local entity whose values will be overwritten.
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://asset',
      'label' => 'This will be overridden',
      'field_is_description' => 'Also this...',
      'field_status' => 'http://example.com/status',
    ])->save();
    Rdf::create([
      'rid' => 'collection',
      'id' => 'http://catalog',
      'field_ar_affiliates' => 'http://asset',
    ])->save();

    $graph = new Graph(static::getTestingGraphs()['sink']);
    $graph->parseFile(__DIR__ . '/../../fixtures/valid_adms.rdf');
    $this->createGraphStore()->replace($graph);

    $this->runPipelineStep('remove_unsupported_data');
    $this->runPipelineStep('add_joinup_vocabularies');
    // Cleanup the 'sink_plus_taxo' graph left after the last step. Normally
    // this cleanup is accomplished by the 'adms_validation' step but we want to
    // avoid running that step in this test.
    $this->pipeline->clearGraph($this->pipeline->getGraphUri('sink_plus_taxo'));

    $state = (new PipelineState())
      ->setStepId('3_way_merge')
      ->setBatchValue('remaining_incoming_ids', ['http://asset' => TRUE]);
    $result = $this->runPipelineStep('3_way_merge', $state);

    // Check that the step ran without any error.
    $this->assertNull($result);

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = Rdf::load('http://asset', ['staging']);

    // Check that incoming values are preseved over local ones.
    $this->assertEquals('Asset', $solution->label());
    $this->assertEquals('This is an Asset.', $solution->get('field_is_description')->value);
    // Check that a missed incoming value is copied from the local entity.
    $this->assertEquals('http://example.com/status', $solution->get('field_status')->target_id);
  }

  /**
   * Test values assignment with a new solution.
   */
  public function testNewSolution() {
    Rdf::create([
      'rid' => 'collection',
      'id' => 'http://catalog',
    ])->save();

    $graph = new Graph(static::getTestingGraphs()['sink']);
    $graph->parseFile(__DIR__ . '/../../fixtures/valid_adms.rdf');
    $this->createGraphStore()->replace($graph);

    $this->runPipelineStep('remove_unsupported_data');
    $this->runPipelineStep('add_joinup_vocabularies');
    // Cleanup the 'sink_plus_taxo' graph left after the last step. Normally
    // this cleanup is accomplished by the 'adms_validation' step but we want to
    // avoid running that step in this test.
    $this->pipeline->clearGraph($this->pipeline->getGraphUri('sink_plus_taxo'));

    $state = (new PipelineState())
      ->setStepId('3_way_merge')
      ->setBatchValue('remaining_incoming_ids', ['http://asset' => FALSE]);
    $result = $this->runPipelineStep('3_way_merge', $state);

    // Check that the step ran without any error.
    $this->assertNull($result);

    // Check that the solution has been assigned to the configured collection.
    $collection = Rdf::load('http://catalog');
    $this->assertEquals('http://asset', $collection->field_ar_affiliates->target_id);

    // Check that, for an empty field, the default value is assigned.
    $solution = Rdf::load('http://asset', ['staging']);
    $this->assertEquals('http://example.com/default-status', $solution->get('field_status')->target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $storage->delete($storage->loadMultiple([
      'http://asset',
      'http://catalog',
    ]));
    parent::tearDown();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Tests\joinup_test\Traits\ConfigTestTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\pipeline\PipelineState;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\taxonomy\Entity\Vocabulary;
use EasyRdf\Graph;

/**
 * Tests the '3_way_merge' process step plugin.
 *
 * @group joinup_federation
 */
class ThreeWayMergeStepTest extends StepTestBase {

  use ConfigTestTrait;

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
    'allowed_formats',
    'field',
    'joinup_sparql',
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

    // Create the language vocabulary. and mapping.
    Vocabulary::create(['vid' => 'language', 'name' => 'Language'])->save();
    // Create the solution RDF type.
    RdfEntityType::create(['rid' => 'solution', 'name' => 'Solution'])->save();
    // Create the collection bundle.
    RdfEntityType::create(['rid' => 'collection', 'name' => 'Community'])->save();

    $this->importConfigs([
       // Create the 'default', 'draft' and 'staging' graphs.
      'sparql_entity_storage.graph.default',
      'sparql_entity_storage.graph.draft',
      'sparql_entity_storage.graph.staging',
      // Create the language vocabulary mapping.
      'sparql_entity_storage.mapping.taxonomy_term.language',
      // Solution and collection mappings.
      'sparql_entity_storage.mapping.rdf_entity.solution',
      'sparql_entity_storage.mapping.rdf_entity.collection',
      // Add some fields for the purpose of this test.
      'field.storage.rdf_entity.field_is_description',
      'field.field.rdf_entity.solution.field_is_description',
      'field.storage.rdf_entity.field_status',
      'field.field.rdf_entity.solution.field_status',
      'field.storage.rdf_entity.field_ar_affiliates',
      'field.field.rdf_entity.collection.field_ar_affiliates',
    ]);

    // Set a default value for field_status.
    $this->container->get('config.factory')
      ->getEditable('field.field.rdf_entity.solution.field_status')
      ->set('default_value', [['target_id' => 'http://example.com/default-status']])
      ->save();

    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'rdf_entity',
      'field_name' => 'field_is_textfield',
    ])->setThirdPartySetting('sparql_entity_storage', 'mapping', [
      'value' => [
        'predicate' => 'http://joinup.eu/not-defined-in-schema/textfield',
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
      'field_name' => 'field_is_textfield',
      'label' => 'Not defined description',
    ])->save();
  }

  /**
   * Test values assignment with an existing solution.
   */
  public function testExistingSolution() {
    Rdf::create([
      'rid' => 'collection',
      'id' => 'http://catalog',
    ])->save();
    // Create a local entity whose values will be overwritten.
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://asset',
      'label' => 'This will be overridden',
      'field_is_description' => 'Also this...',
      'field_status' => 'http://example.com/status',
      'field_is_textfield' => 'This value should not be empty after re-import.',
      'collection' => 'http://catalog',
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
    $this->runPipelineStep('3_way_merge', $state);

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = Rdf::load('http://asset', ['staging']);

    // Check that incoming values are preserved over local ones.
    $this->assertEquals('Asset', $solution->label());
    $this->assertEquals('This is an Asset.', $solution->get('field_is_description')->value);
    // Ensure that when an incoming value is empty, the local value gets emptied
    // too e.g. related solutions.
    $this->assertTrue($solution->get('field_status')->isEmpty());
    // Local values persist over incoming values when the field is not defined
    // in schema e.g. workflow status.
    $this->assertFalse($solution->get('field_is_textfield')->isEmpty());
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
    $this->runPipelineStep('3_way_merge', $state);

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

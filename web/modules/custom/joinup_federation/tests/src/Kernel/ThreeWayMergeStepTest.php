<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
    ])->save();

    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../solution/config/install/rdf_entity.mapping.rdf_entity.solution.yml'));
    RdfEntityMapping::create($mapping)->save();

    // Create the owner bundle.
    RdfEntityType::create(['rid' => 'owner', 'name' => 'Owner'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../owner/config/install/rdf_entity.mapping.rdf_entity.owner.yml'));
    RdfEntityMapping::create($mapping)->save();

    // Create the contact information bundle.
    RdfEntityType::create(['rid' => 'contact_information', 'name' => 'Contact info'])->save();
    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../contact_information/config/install/rdf_entity.mapping.rdf_entity.contact_information.yml'));
    RdfEntityMapping::create($mapping)->save();
  }

  /**
   * Test the 3-way merge.
   */
  public function test() {
    // Create a local entity whose values will be overwritten.
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://asset',
      'label' => 'This will be overridden',
      'field_is_description' => 'Also this...',
      'field_status' => 'http://example.com/status',
    ])->save();

    $graph = new Graph(static::getTestingGraphs()['sink']);
    $graph->parseFile(__DIR__ . '/../../fixtures/valid_adms.rdf');
    $this->createGraphStore()->replace($graph);

    // Check that 'http://asset' exists before import, in the default graph.
    $this->assertNotNull(Rdf::load('http://asset', ['default']));
    // Check that the publisher and contact info are missed from default graph.
    $this->assertNull(Rdf::load('http://publisher', ['default']));
    $this->assertNull(Rdf::load('http://contact', ['default']));

    $this->runPipelineStep('remove_unsupported_data');
    $this->runPipelineStep('add_joinup_vocabularies');
    // Cleanup the 'sink_plus_taxo' graph left after the last step. Normally
    // this cleanup is accomplished by the 'adms_validation' step but we want to
    // avoid running that step in this test.
    $this->pipeline->clearGraph($this->pipeline->getGraphUri('sink_plus_taxo'));

    $result = $this->runPipelineStep('3_way_merge');

    // Check that the step ran without any error.
    $this->assertNull($result);

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = Rdf::load('http://asset');

    // Check that an existing entity values are overridden.
    $this->assertEquals('Asset', $solution->label());
    $this->assertEquals('This is an Asset.', $solution->get('field_is_description')->value);
    $this->assertTrue($solution->get('field_status')->isEmpty());

    // Check that new entities were created in the 'default' graph and were
    // removed from the staging graph.
    $this->assertNotNull(Rdf::load('http://publisher', ['default']));
    $this->assertNotNull(Rdf::load('http://contact', ['default']));
    $this->assertNull(Rdf::load('http://publisher', ['staging']));
    $this->assertNull(Rdf::load('http://contact', ['staging']));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $storage->delete($storage->loadMultiple([
      'http://asset',
      'http://publisher',
      'http://contact',
    ]));
    parent::tearDown();
  }

}

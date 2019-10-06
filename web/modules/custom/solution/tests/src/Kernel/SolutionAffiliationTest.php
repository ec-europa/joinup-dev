<?php

declare(strict_types = 1);

namespace Drupal\Tests\solution\Kernel;

use Drupal\Core\Serialization\Yaml;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;
use Drupal\sparql_entity_storage\Entity\SparqlGraph;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests solution affiliation.
 *
 * @group solution
 */
class SolutionAffiliationTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'allowed_formats',
    'cached_computed_field',
    'comment',
    'contact_information',
    'ds',
    'facets',
    'field',
    'field_group',
    'file',
    'file_url',
    'image',
    'inline_entity_form',
    'joinup_core',
    'joinup_sparql',
    'link',
    'matomo_reporting_api',
    'node',
    'oe_newsroom_newsletter',
    'og',
    'options',
    'owner',
    'rdf_schema_field_validation',
    'rdf_draft',
    'rdf_entity',
    'rdf_taxonomy',
    'search_api',
    'search_api_field',
    'smart_trim',
    'solution',
    'sparql_entity_storage',
    'state_machine',
    'system',
    'taxonomy',
    'text',
    'tour',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();

    RdfEntityType::create(['rid' => 'collection'])->save();

    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('rdf_entity');
    $this->installConfig([
      'joinup_core',
      'rdf_draft',
      'rdf_entity',
      'solution',
      'contact_information',
      'owner',
      'sparql_entity_storage',
    ]);

    $mapping = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/sparql_entity_storage.mapping.rdf_entity.collection.yml'));
    SparqlMapping::create($mapping)->save();
    $field_storage = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.storage.rdf_entity.field_ar_affiliates.yml'));
    FieldStorageConfig::create($field_storage)->save();
    $field_config = Yaml::decode(file_get_contents(__DIR__ . '/../../../../collection/config/install/field.field.rdf_entity.collection.field_ar_affiliates.yml'));
    FieldConfig::create($field_config)->save();
  }

  /**
   * Tests orphan solutions.
   */
  public function testOrphanSolution(): void {
    SparqlGraph::create([
      'id' => 'arbitrary',
      'name' => 'Non-official graph',
    ])->save();
    SparqlMapping::loadByName('rdf_entity', 'solution')
      ->addGraphs(['arbitrary' => 'http://example.com/graph/arbitrary'])
      ->save();

    // Check that creating an orphan solution is allowed in a nonofficial graph.
    Rdf::create([
      'rid' => 'solution',
      'graph' => 'arbitrary',
      'id' => 'http://example.com/solution',
      'label' => 'Test solution',
      'field_is_state' => 'validated',
    ])->save();

    // Check that creating an orphan solution in a normal graph is disallowed.
    $this->expectExceptionObject(new \Exception("Solution 'http://example.com/solution' should have a parent collection."));
    Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution',
      'label' => 'Test solution',
      'field_is_state' => 'validated',
    ])->save();
  }

  /**
   * Tests orphan solutions.
   */
  public function testAffiliation(): void {
    foreach (range(1, 3) as $delta) {
      Rdf::create([
        'rid' => 'collection',
        'id' => "http://example.com/collection/{$delta}",
        'label' => "Collection {$delta}",
      ])->save();
      // Warm the cache.
      Rdf::load("http://example.com/collection/{$delta}");
    }
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution',
      'label' => 'Test solution',
      'collection' => [
        // API allows an 1..N cardinality while UI doesn't.
        'http://example.com/collection/1',
        'http://example.com/collection/2',
      ],
      'field_is_state' => 'validated',
    ]);
    $solution->save();

    // Check that collection 1 has the correct affiliates.
    $affiliates = Rdf::load('http://example.com/collection/1')->get('field_ar_affiliates');
    $this->assertCount(1, $affiliates);
    $this->assertSame('http://example.com/solution', $affiliates->target_id);
    // Check that collection 2 has the correct affiliates.
    $affiliates = Rdf::load('http://example.com/collection/2')->get('field_ar_affiliates');
    $this->assertCount(1, $affiliates);
    $this->assertSame('http://example.com/solution', $affiliates->target_id);

    // Collection 1 is preserved but collection 2 is replaced with 3.
    $solution->set('collection', [
      'http://example.com/collection/1',
      'http://example.com/collection/3',
    ])->save();

    // Check that collection 1 has the correct affiliates.
    $affiliates = Rdf::load('http://example.com/collection/1')->get('field_ar_affiliates');
    $this->assertCount(1, $affiliates);
    $this->assertSame('http://example.com/solution', $affiliates->target_id);
    // Check that collection 2 has no affiliates.
    $affiliates = Rdf::load('http://example.com/collection/2')->get('field_ar_affiliates');
    $this->assertTrue($affiliates->isEmpty());
    // Check that collection 3 has the correct affiliates.
    $affiliates = Rdf::load('http://example.com/collection/3')->get('field_ar_affiliates');
    $this->assertCount(1, $affiliates);
    $this->assertSame('http://example.com/solution', $affiliates->target_id);

    // Keep only collection 2.
    $solution->set('collection', 'http://example.com/collection/2')->save();

    // Check that collection 1 has no affiliates.
    $affiliates = Rdf::load('http://example.com/collection/1')->get('field_ar_affiliates');
    $this->assertTrue($affiliates->isEmpty());
    // Check that collection 2 has the correct affiliates.
    $affiliates = Rdf::load('http://example.com/collection/2')->get('field_ar_affiliates');
    $this->assertCount(1, $affiliates);
    $this->assertSame('http://example.com/solution', $affiliates->target_id);
    // Check that collection 3 has no affiliates.
    $affiliates = Rdf::load('http://example.com/collection/3')->get('field_ar_affiliates');
    $this->assertTrue($affiliates->isEmpty());

    // Save again the solution without any affiliation changes.
    $solution->save();

    // Check that nothing has been changed.
    $affiliates = Rdf::load('http://example.com/collection/1')->get('field_ar_affiliates');
    $this->assertTrue($affiliates->isEmpty());
    $affiliates = Rdf::load('http://example.com/collection/2')->get('field_ar_affiliates');
    $this->assertCount(1, $affiliates);
    $this->assertSame('http://example.com/solution', $affiliates->target_id);
    $affiliates = Rdf::load('http://example.com/collection/3')->get('field_ar_affiliates');
    $this->assertTrue($affiliates->isEmpty());
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete RDF entities.
    $rdf_entity_keys = [
      'collection/1',
      'collection/2',
      'collection/3',
      'solution',
    ];
    foreach ($rdf_entity_keys as $key) {
      Rdf::load("http://example.com/$key")->delete();
    }
  }

}

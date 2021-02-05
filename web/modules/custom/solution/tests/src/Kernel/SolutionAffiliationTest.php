<?php

declare(strict_types = 1);

namespace Drupal\Tests\solution\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\joinup_test\Traits\ConfigTestTrait;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityType;

/**
 * Tests solution affiliation.
 *
 * @group solution
 */
class SolutionAffiliationTest extends KernelTestBase {

  use ConfigTestTrait;
  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'og',
    'rdf_schema_field_validation',
    'rdf_draft',
    'rdf_entity',
    'solution',
    'sparql_entity_storage',
    'state_machine',
    'system',
    'taxonomy',
    'user',
    'workflow_state_permission',
  ];

  /**
   * {@inheritdoc}
   */
  protected function bootEnvironment() {
    parent::bootEnvironment();
    $this->setUpSparql();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    RdfEntityType::create(['rid' => 'collection'])->save();
    RdfEntityType::create(['rid' => 'solution'])->save();

    $this->installEntitySchema('user');
    $this->installEntitySchema('rdf_entity');
    $this->installConfig([
      'rdf_draft',
      'sparql_entity_storage',
    ]);

    $this->importConfigs([
      'sparql_entity_storage.mapping.rdf_entity.collection',
      'sparql_entity_storage.mapping.rdf_entity.solution',
      'field.storage.rdf_entity.field_ar_affiliates',
      'field.field.rdf_entity.collection.field_ar_affiliates',
      'field.storage.rdf_entity.field_ar_state',
      'field.field.rdf_entity.collection.field_ar_state',
      'field.storage.rdf_entity.field_is_state',
      'field.field.rdf_entity.solution.field_is_state',
    ]);
  }

  /**
   * Tests orphan solutions.
   */
  public function testOrphanSolution(): void {
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
   *
   * @dataProvider affiliationProvider
   */
  public function testAffiliation(string $collection_state, string $solution_state): void {
    foreach (range(1, 3) as $delta) {
      Rdf::create([
        'rid' => 'collection',
        'id' => "http://example.com/collection/{$delta}",
        'label' => "Collection {$delta}",
        'field_ar_state' => $collection_state,
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
      'field_is_state' => $solution_state,
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
   * Data provider for the testAffiliation test.
   *
   * @return array
   *   A list of tests for the testAffiliation test.
   */
  public function affiliationProvider(): array {
    return [
      ['validated', 'validated'],
      ['validated', 'draft'],
      ['draft', 'validated'],
      ['draft', 'draft'],
    ];
  }

  /**
   * Tests that adding a solution only adds a relation to the appropriate graph.
   */
  public function testNoOrphans(): void {
    Rdf::create([
      'rid' => 'collection',
      'id' => "http://example.com/collection/1",
      'label' => "Collection 1",
      'field_ar_state' => 'validated',
    ])->save();

    $solution = Rdf::create([
      'rid' => 'solution',
      'id' => 'http://example.com/solution',
      'label' => 'Test solution',
      'collection' => [
        'http://example.com/collection/1',
        'http://example.com/collection/2',
      ],
      'field_is_state' => 'draft',
    ]);
    $solution->save();

    $query = <<<QUERY
SELECT COUNT(*) as ?count
WHERE {
  GRAPH ?g {
    ?s ?p <{$solution->id()}> .
    FILTER NOT EXISTS { ?s a ?type } .
  }
}
QUERY;

    $results = $this->container->get('sparql.endpoint')->query($query);
    $this->assertSame(0, $results[0]->count->getValue());
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $rdf_entity_keys = [
      'collection/1',
      'collection/2',
      'collection/3',
      'solution',
    ];
    // Delete RDF entities.
    foreach ($rdf_entity_keys as $key) {
      if ($entity = Rdf::load("http://example.com/$key")) {
        $entity->delete();
      }
    }
  }

}

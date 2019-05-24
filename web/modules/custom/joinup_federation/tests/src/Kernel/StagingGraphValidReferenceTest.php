<?php

namespace Drupal\Tests\joinup_federation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests referencing RDF entities when the host entity is in the staging graph.
 *
 * @group joinup_federation
 */
class StagingGraphValidReferenceTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'joinup_federation',
    'joinup_federation_staging_graph_test',
    'joinup_sparql',
    'rdf_draft',
    'rdf_entity',
    'sparql_entity_storage',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();
    $this->installConfig([
      'joinup_federation',
      'joinup_federation_staging_graph_test',
      'rdf_draft',
      'rdf_entity',
      'sparql_entity_storage',
    ]);
    $this->installEntitySchema('user');
  }

  /**
   * Tests referencing entities when the host entity is in the staging graph.
   */
  public function test() {
    // Create three referred entities, one for each graph.
    Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/plum',
      'label' => 'Plum',
      'graph' => 'default',
    ])->save();
    Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/cherry',
      'label' => 'Cherry',
      'graph' => 'draft',
    ])->save();
    Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/melon',
      'label' => 'Melon',
      'graph' => 'staging',
    ])->save();

    // Create the referring entity in 'staging' graph.
    $apple = Rdf::create([
      'rid' => 'fruit',
      'id' => 'http://example.com/apple',
      'label' => 'Apple',
      'graph' => 'staging',
      'related' => [
        'http://example.com/plum',
        'http://example.com/cherry',
        'http://example.com/melon',
      ],
    ]);

    /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $related */
    $related = $apple->get('related');

    // The host entity, in 'staging' graph, refers entities from all graphs.
    $this->assertEmpty($apple->validate());

    // Check that ::referencedEntities() works correctly.
    $this->assertCount(3, $related->referencedEntities());
    $this->assertEquals('default', $related->referencedEntities()[0]->get('graph')->target_id);
    $this->assertEquals('draft', $related->referencedEntities()[1]->get('graph')->target_id);
    $this->assertEquals('staging', $related->referencedEntities()[2]->get('graph')->target_id);

    // Check that the magic 'entity' property retrieves from the correct graph.
    $this->assertEquals('default', $related->entity->get('graph')->target_id);

    // Create a 'staging' version of Plum.
    (clone Rdf::load('http://example.com/plum'))->set('graph', 'staging')->save();
    $this->assertEquals('default', Rdf::load('http://example.com/plum')->get('graph')->target_id);
    $this->assertEquals('staging', Rdf::load('http://example.com/plum', ['staging'])->get('graph')->target_id);

    // The host entity still validates.
    $this->assertEmpty($apple->validate());

    // Check that ::referencedEntities() works correctly.
    $this->assertCount(3, $related->referencedEntities());
    $this->assertEquals('staging', $related->referencedEntities()[0]->get('graph')->target_id);
    $this->assertEquals('draft', $related->referencedEntities()[1]->get('graph')->target_id);
    $this->assertEquals('staging', $related->referencedEntities()[2]->get('graph')->target_id);

    // Check that the magic 'entity' property retrieves from the correct graph,
    // but first recompute the property, as it has been computed previously.
    $related->target_id = 'http://example.com/plum';
    $this->assertEquals('staging', $related->entity->get('graph')->target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $graph_ids = $this->container->get('sparql.graph_handler')->getEntityTypeGraphIds('rdf_entity');
    foreach (['apple', 'plum', 'cherry', 'melon'] as $fruit) {
      if ($entity = Rdf::load("http://example.com/$fruit", $graph_ids)) {
        $entity->delete();
      }
    }
    parent::tearDown();
  }

}

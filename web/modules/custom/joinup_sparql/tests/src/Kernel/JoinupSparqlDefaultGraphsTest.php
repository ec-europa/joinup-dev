<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_sparql\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\Entity\RdfEntityGraph;
use Drupal\rdf_entity\Entity\RdfEntityMapping;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Tests the default graphs event subscriber.
 *
 * @group joinup_sparql
 */
class JoinupSparqlDefaultGraphsTest extends KernelTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'joinup_sparql',
    'rdf_draft',
    'rdf_entity',
    'rdf_entity_graph_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpSparql();
    $this->installConfig(['rdf_draft', 'rdf_entity', 'rdf_entity_graph_test']);

    // Create an arbitrary graph.
    RdfEntityGraph::create(['id' => 'arbitrary', 'label' => 'Arbitrary'])
      // Is heavier than the 'draft' graph..
      ->setWeight(20)
      ->save();
    RdfEntityMapping::loadByName('rdf_entity', 'fruit')
      ->addGraphs(['arbitrary' => "http://example.com/fruit/graph/arbitrary"])
      ->save();
  }

  /**
   * Tests the default graphs event subscriber.
   */
  public function testDefaultGraphs(): void {
    $id = 'http://example.com/apple';
    Rdf::create([
      'id' => $id,
      'rid' => 'fruit',
      'label' => 'Apple',
      'graph' => 'arbitrary',
    ])->save();

    // Check that loading explicitly from a candidate list, that includes the
    // 'arbitrary' graph, works.
    $this->assertNotNull(Rdf::load($id, ['draft', 'arbitrary']));
    // Same for entity query.
    $ids = $this->getQuery()
      ->graphs(['draft', 'arbitrary'])
      ->condition('id', $id)
      ->execute();
    $this->assertCount(1, $ids);
    $this->assertEquals($id, reset($ids));

    // Check that loading the entity from the default graphs returns NULL.
    $this->assertNull(Rdf::load($id));
    // Same for entity query.
    $ids = $this->getQuery()->condition('id', $id)->execute();
    $this->assertEmpty($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    Rdf::load('http://example.com/apple', ['arbitrary'])->delete();
    parent::tearDown();
  }

  /**
   * Returns the entity query.
   *
   * @return \Drupal\rdf_entity\Entity\Query\Sparql\SparqlQueryInterface
   *   The SPARQL entity query.
   */
  protected function getQuery() {
    return $this->container
      ->get('entity_type.manager')
      ->getStorage('rdf_entity')
      ->getQuery();
  }

}

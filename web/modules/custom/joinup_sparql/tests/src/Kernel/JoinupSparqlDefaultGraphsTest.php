<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_sparql\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the default graphs event subscriber.
 *
 * @group joinup_sparql
 */
class JoinupSparqlDefaultGraphsTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'joinup_sparql',
    'joinup_sparql_test',
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
      'joinup_sparql_test',
      'rdf_draft',
      'sparql_entity_storage',
    ]);
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
   * @return \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface
   *   The SPARQL entity query.
   */
  protected function getQuery(): SparqlQueryInterface {
    return $this->container
      ->get('entity_type.manager')
      ->getStorage('rdf_entity')
      ->getQuery();
  }

}

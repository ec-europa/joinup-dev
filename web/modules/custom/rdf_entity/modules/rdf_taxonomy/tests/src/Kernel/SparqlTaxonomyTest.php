<?php

declare(strict_types = 1);

namespace Drupal\rdf_taxonomy\Tests;

use Drupal\Tests\rdf_entity\Kernel\RdfKernelTestBase;
use Drupal\taxonomy\Entity\Term;

/**
 * Tests Entity Query functionality of the Sparql backend.
 *
 * @see \Drupal\KernelTests\Core\Entity\EntityQueryTest
 *
 * @group Entity
 */
class SparqlTaxonomyTest extends RdfKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_test',
    'language',
    'rdf_taxonomy',
    'rdf_taxonomy_test',
    'taxonomy',
  ];

  /**
   * A list of query results.
   *
   * @var array
   */
  protected $queryResults;

  /**
   * A list of bundle machine names created for this test.
   *
   * @var string[]
   */
  protected $bundles;

  /**
   * Dummy reference entities.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $dummyEntities;

  /**
   * The entity reference field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['rdf_taxonomy_test']);

    $vocabulary = 'taxonomy_test';
    $prefix = "http://$vocabulary/";
    for ($i = 0; $i < 100; $i++) {
      $id = sprintf("%s%03d", $prefix, $i + 1);
      $values = [
        'tid' => $id,
        'vid' => $vocabulary,
        'name' => $this->randomMachineName(),
        'description' => $this->randomString(255),
      ];
      Term::create($values)->save();
    }

    $results = $this->container->get('entity_type.manager')
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->condition('vid', 'taxonomy_test')
      ->execute();
    $this->assertCount($i, $results, "${i} terms were loaded successfully.");
  }

  /**
   * Tests that a term insert saves the proper values.
   */
  public function testTaxonomyInsert() {
    $values = [
      'tid' => 'htp://taxonomy_test/100',
      'vid' => 'taxonomy_test',
      'name' => $this->randomMachineName(),
      'description' => $this->randomString(255),
    ];

    $term = Term::create($values);
    $term->save();
    $taxonomy_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $term = $taxonomy_storage->loadUnchanged($term->id());
    foreach ($values as $field => $expected_value) {
      $actual_value = $term->get($field)->first()->getValue();
      $actual_value = is_array($actual_value) ? reset($actual_value) : $actual_value;
      $this->assertEquals($expected_value, $actual_value);
    }

    $terms = $taxonomy_storage->loadByProperties([
      'vid' => 'taxonomy_test',
    ]);
    $this->assertCount(101, $terms);
  }

  /**
   * Tests that an entity can reference a taxonomy and can be queried normally.
   */
  public function testTaxonomyReference() {
    $storage = $this->container->get('entity_type.manager')->getStorage('rdf_entity');
    $entity_multi_label = $this->randomMachineName();
    $storage->create([
      'label' => $entity_multi_label,
      'rid' => 'dummy',
      'field_taxonomy' => [
        'http://taxonomy_test/009',
      ],
    ])->save();

    $results = $storage->getQuery()
      ->condition('field_taxonomy', 'http://taxonomy_test/009')
      ->execute();
    $id = reset($results);
    $entity = $storage->load($id);

    $actual_value = $entity->get('field_taxonomy')->first()->target_id;
    $this->assertSame('http://taxonomy_test/009', $actual_value);
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Delete all data produced by testing module.
    foreach (['published', 'draft'] as $graph) {
      $query = <<<EndOfQuery
DELETE {
  GRAPH <http://example.com/rdf_taxonomy/$graph> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <http://example.com/rdf_taxonomy/$graph> {
    ?entity ?field ?value
  }
}
EndOfQuery;
      $this->sparql->query($query);
    }

    parent::tearDown();
  }

}

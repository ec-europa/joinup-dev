<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the RdfTerm weight.
 *
 * @group rdf_taxonomy
 */
class SparqlTermWeightTest extends KernelTestBase {

  use SparqlConnectionTrait;

  /**
   * Term data as an array keyed by term name and having term weight as value.
   *
   * @var int[]
   */
  const DATA = [
    'Abc' => 3,
    'Bcd' => 3,
    'Cde' => 0,
    'Xyz' => -1,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_entity',
    'rdf_taxonomy',
    'sparql_entity_storage',
    'taxonomy',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();
    $this->installConfig(['sparql_entity_storage']);

    // Create a test vocabulary.
    Vocabulary::create(['vid' => 'test_vocab'])->save();
    SparqlMapping::create([
      'entity_type_id' => 'taxonomy_term',
      'bundle' => 'test_vocab',
    ])->setRdfType('http://example.com/test_vocab')
      ->setGraphs(['default' => 'http://example.com/test_vocab/graph'])
      ->setEntityIdPlugin('default')
      ->setMappings([
        'vid' => [
          'target_id' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#inScheme',
            'format' => 'resource',
          ],
        ],
        'name' => [
          'value' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#prefLabel',
            'format' => 't_literal',
          ],
        ],
        'parent' => [
          'target_id' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#broaderTransitive',
            'format' => 'resource',
          ],
        ],
        'description' => [
          'value' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#definition',
            'format' => 't_literal',
          ],
        ],
      ])->save();
  }

  /**
   * Tests the RdfTerm weight.
   */
  public function testTermWeight(): void {
    /** @var \Drupal\rdf_taxonomy\TermRdfStorage $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');

    // Returns an ordered list of term labels from the backend.
    $get_labels = function () use ($storage): array {
      $this->createTerms();
      $tree = $storage->loadTree('test_vocab');
      return array_map(function (\stdClass $term): string {
        return $term->name;
      }, $tree);
    };

    // Check that with no weight mapping the terms are ordered alphabetically.
    $this->assertSame(['Abc', 'Bcd', 'Cde', 'Xyz'], $get_labels());

    // Add the 'weight' mapping.
    SparqlMapping::loadByName('taxonomy_term', 'test_vocab')
      ->addMappings([
        'weight' => [
          'value' => [
            'predicate' => 'http://example.com/test_vocab/weight',
            'format' => 'xsd:integer',
          ],
        ],
      ])
      ->save();

    // Check that with weight mapping the terms are ordered by weight and
    // eventually alphabetically by label.
    $this->assertSame(['Xyz', 'Cde', 'Abc', 'Bcd'], $get_labels());
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->emptyTestVocabulary();
    parent::tearDown();
  }

  /**
   * Creates testing terms.
   */
  protected function createTerms(): void {
    // Ensure that the vocabulary is empty.
    $this->emptyTestVocabulary();
    foreach (static::DATA as $name => $weight) {
      RdfTerm::create([
        'vid' => 'test_vocab',
        'name' => $name,
      ])->setWeight($weight)
        ->save();
    }
  }

  /**
   * Empties the 'test_vocab' vocabulary.
   */
  protected function emptyTestVocabulary(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    if ($tids = $storage->getQuery()->condition('vid', 'test_vocab')->execute()) {
      $storage->delete($storage->loadMultiple($tids));
    }
  }

}

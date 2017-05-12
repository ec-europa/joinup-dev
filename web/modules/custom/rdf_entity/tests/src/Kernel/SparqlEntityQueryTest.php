<?php

namespace Drupal\rdf_entity\Tests;

use Drupal\rdf_entity\Entity\Rdf;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Tests Entity Query functionality of the Sparql backend.
 *
 * @see \Drupal\KernelTests\Core\Entity\EntityQueryTest
 *
 * @group Entity
 */
class SparqlEntityQueryTest extends JoinupKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field_test',
    'datetime',
    'language',
  ];

  /**
   * A list of query results.
   *
   * @var array
   */
  protected $queryResults;

  /**
   * The query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $factory;

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create 10 referable entities.
    $bundle = 'dummy';
    $prefix = "http://$bundle.example.com/";
    for ($i = 0; $i < 10; $i++) {
      $id = sprintf("%s%03d", $prefix, $i + 1);
      $dummy = Rdf::create([
        'id' => $id,
        'label' => 'dummy label ' . ($i + 1),
        'rid' => $bundle,
        'field_text' => 'text field ' . ($i % 2),
      ]);
      $dummy->save();
      $this->dummyEntities[$i] = $dummy;
    }

    $bundle = 'multifield';
    $prefix = "http://$bundle.example.com/";
    foreach ($this->getTestEntityValues() as $i => $values) {
      $id = sprintf("%s%03d", $prefix, $i + 1);
      $values += [
        'id' => $id,
        'rid' => $bundle,
      ];
      Rdf::create($values)->save();
    }

    $this->factory = \Drupal::service('entity.query');
  }

  /**
   * Provides entity values for the test's setup.
   *
   * @return array
   *   An array of entity values.
   */
  protected function getTestEntityValues() {
    $time = \Drupal::time()->getRequestTime();
    $return = [];
    // Entity 001.
    $return[] = [
      'field_text' => [
        'value' => 'test text 1',
        'format' => 'plain_text',
      ],
      'field_text_multi' => [
        'test text multi 1',
      ],
      'field_date' => $time,
      'field_reference' => [
        $this->dummyEntities[4]->id(),
      ],
    ];

    // Entity 002.
    $return[] = [
      'field_text' => [
        'value' => '<html><body><p>Hello world!</p></body></html>',
        'format' => 'full_html',
      ],
      'field_text_multi' => [
        'test text multi 1',
      ],
    ];

    // Entity 003.
    $return[] = [
      'field_text' => 'test text 1',
      'field_text_multi' => [
        'test text multi 2',
      ],
      'field_date' => $time,
      'field_reference' => [
        $this->dummyEntities[2]->id(),
        $this->dummyEntities[3]->id(),
        $this->dummyEntities[4]->id(),
      ],
    ];

    // Entity 004.
    $return[] = [
      'field_reference' => [
        $this->dummyEntities[0]->id(),
        $this->dummyEntities[1]->id(),
        $this->dummyEntities[2]->id(),
        $this->dummyEntities[3]->id(),
        $this->dummyEntities[4]->id(),
        $this->dummyEntities[5]->id(),
        $this->dummyEntities[6]->id(),
        $this->dummyEntities[7]->id(),
        $this->dummyEntities[8]->id(),
        $this->dummyEntities[9]->id(),
      ],
    ];

    // Entity 005.
    $return[] = [
      'field_text' => 'sample string 2',
      'field_text_multi' => [
        'test text multi 1',
        'test text multi 3',
        'test text multi 2',
      ],
    ];

    // Entity 006.
    $return[] = [
      'field_text' => 'sample string 1',
      'field_text_multi' => [
        'test text multi 1',
        'sample string 2',
      ],
      'field_reference' => [
        $this->dummyEntities[8]->id(),
        $this->dummyEntities[9]->id(),
      ],
    ];

    // Entity 007.
    $return[] = [
      'field_text' => 'test text 1',
      'field_text_multi' => [
        'test text multi 1',
      ],
      'field_date' => $time,
      'field_reference' => [
        $this->dummyEntities[5]->id(),
      ],
    ];

    // Entity 008.
    $return[] = [
      'field_text' => 'test text 1',
      'field_text_multi' => [
        'test text multi 1',
      ],
      'field_date' => $time,
      'field_reference' => [
        $this->dummyEntities[8]->id(),
      ],
    ];

    // Entity 009.
    $return[] = [
      'field_text' => 'test text 1',
      'field_text_multi' => [
        'test text multi 1',
      ],
      'field_date' => $time,
      'field_reference' => [
        $this->dummyEntities[1]->id(),
      ],
    ];

    // Entity 010.
    $return[] = [
      'field_text' => 'test text 1',
      'field_text_multi' => [
        'test text multi 1',
      ],
      'field_date' => $time,
      'field_reference' => [
        $this->dummyEntities[6]->id(),
      ],
    ];

    return $return;
  }

  /**
   * Tests basic functionality related to Id and bundle filtering.
   */
  public function testIdBundleFilters() {
    // Checks the '=' operator for IDs for a valid ID and a valid bundle.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/001')
      ->condition('rid', 'multifield')
      ->execute();
    $this->assertResult('http://multifield.example.com/001');

    // Checks the '=' operator for IDs for a valid ID and a different bundle.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/001')
      ->condition('rid', 'dummy')
      ->execute();
    $this->assertResult();

    // Checks the '!=' operator for the bundle.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', [
        'http://multifield.example.com/001',
        'http://dummy.example.com/002',
      ], 'IN')
      ->condition('rid', 'multifield', '!=')
      ->execute();
    $this->assertResult('http://dummy.example.com/002');

    // Checks the IN operator for the bundle.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', [
        'http://dummy.example.com/002',
        'http://multifield.example.com/001',
      ], 'IN')
      ->condition('rid', ['dummy', 'multifield'], 'IN')
      ->sort('id')
      ->execute();
    $this->assertResult('http://dummy.example.com/002', 'http://multifield.example.com/001');

    // Checks the NOT IN operator for the bundle.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', [
        'http://multifield.example.com/001',
        'http://dummy.example.com/002',
      ], 'IN')
      ->condition('rid', ['multifield'], 'NOT IN')
      ->execute();
    $this->assertResult('http://dummy.example.com/002');

    // Checks the 'IN' operator for IDs.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', [
        'http://multifield.example.com/000',
        'http://multifield.example.com/001',
        'http://multifield.example.com/002',
      ], 'IN')
      ->condition('rid', 'multifield')
      ->execute();
    $this->assertResult('http://multifield.example.com/001', 'http://multifield.example.com/002');

    // Checks the '=' operator for IDs for an invalid ID.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/000')
      ->condition('rid', 'multifield')
      ->execute();
    $this->assertResult();

    // Checks the '!=' operator for IDs for a valid ID.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/001', '!=')
      ->condition('rid', 'multifield')
      ->execute();
    $this->assertResult('http://multifield.example.com/002', 'http://multifield.example.com/003', 'http://multifield.example.com/004', 'http://multifield.example.com/005', 'http://multifield.example.com/006', 'http://multifield.example.com/007', 'http://multifield.example.com/008', 'http://multifield.example.com/009', 'http://multifield.example.com/010');

    // Checks the 'NOT IN' operator for IDs for a valid ID.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', [
        'http://multifield.example.com/002',
        'http://multifield.example.com/003',
        'http://multifield.example.com/004',
        'http://multifield.example.com/005',
        'http://multifield.example.com/006',
        'http://multifield.example.com/007',
        'http://multifield.example.com/008',
        'http://multifield.example.com/009',
        'http://multifield.example.com/010',
      ], 'NOT IN')
      ->condition('rid', 'multifield')
      ->execute();
    $this->assertResult('http://multifield.example.com/001');

    // Try to fetch a NULL ID.
    $this->setExpectedException('Exception', 'The value cannot be NULL for conditions related to the Id and bundle keys.');
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', NULL)
      ->condition('rid', 'multifield')
      ->execute();

    // Try to filter ID with an invalid operator.
    $this->setExpectedException('Exception', "Only '=', '!=', '<>', 'IN', 'NOT IN' operators are allowed for the Id and bundle keys.");
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/003', 'LIKE')
      ->condition('rid', 'multifield')
      ->execute();

    // Try to fetch a NULL bundle.
    $this->setExpectedException('Exception', 'The value cannot be NULL for conditions related to the Id and bundle keys.');
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/002')
      ->condition('rid', NULL)
      ->execute();

    // Try to filter bundle with an invalid operator.
    $this->setExpectedException('Exception', "Only '=', '!=', '<>', 'IN', 'NOT IN' operators are allowed for the Id and bundle keys.");
    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('id', 'http://multifield.example.com/003')
      ->condition('rid', 'multi', 'STARTS WITH')
      ->execute();
  }

  /**
   * Tests basic functionality.
   *
   * The queries here are very simple only to ensure proper functionality of the
   * basic conditions.
   */
  public function testBaseEntityQueryFilters() {
    // Submit an empty query.
    $this->queryResults = $this->factory->get('rdf_entity')
      ->sort('id')
      ->execute();
    $this->assertCount(20, $this->queryResults);

    // Submit an empty 'OR' query.
    $this->queryResults = $this->factory->get('rdf_entity', 'OR')
      ->sort('id')
      ->execute();
    $this->assertCount(20, $this->queryResults);

    $this->queryResults = $this->factory->get('rdf_entity')
      ->exists('field_text')
      ->condition("field_text.format", 'plain_text')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/001');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->exists("field_text.format")
      ->condition('field_text', '<body>', 'LIKE')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/002');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('field_text', '<p>', 'CONTAINS')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/002');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('field_text', '<html>', 'STARTS WITH')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/002');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('field_text', '</html>', 'ENDS WITH')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/002');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('field_text', '</html>', 'ENDS WITH')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/002');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->condition('field_reference', $this->dummyEntities[6]->id())
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/004', 'http://multifield.example.com/010');

    $this->queryResults = $this->factory->get('rdf_entity')
      ->exists('field_text.format')
      ->notExists('field_date')
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/002');

    // OR conjunctions.
    $this->queryResults = $this->factory->get('rdf_entity', 'OR')
      ->exists('field_text.format')
      ->condition('field_reference', $this->dummyEntities[6]->id())
      ->sort('id')
      ->execute();
    $this->assertResult('http://multifield.example.com/001', 'http://multifield.example.com/002', 'http://multifield.example.com/004', 'http://multifield.example.com/010');
  }

  /**
   * Tests more complex functionality.
   */
  public function testNestedConditionGroups() {
    $query = $this->factory->get('rdf_entity', 'OR');

    // Entity 001 should match.
    $condition = $query->andConditionGroup();
    $condition->exists('field_text.format');
    $condition->condition('field_text.value', 'test', 'CONTAINS');
    $condition->condition('field_text.format', ['plain_text', 'filtered_html'], 'IN');
    $query->condition($condition);

    // Entity 002 should match.
    $condition = $query->orConditionGroup();
    $condition->condition('field_text', '<html', 'STARTS WITH');
    $condition->condition('field_text.value', '/html>', 'ENDS WITH');
    // Entity 006 should match.
    $subcondition = $query->andConditionGroup();
    $subcondition->condition('field_text_multi', 'test text multi 1');
    $subcondition->condition('field_text_multi', 'sample string 2', 'CONTAINS');
    $condition->condition($subcondition);

    // Entity 004 should match.
    $subcondition = $query->andConditionGroup();
    $subcondition->condition('field_reference', $this->dummyEntities[4]->id());
    $subcondition->notExists('field_date');
    $condition->condition($subcondition);
    $query->condition($condition);
    $this->queryResults = $query->sort('id')->execute();
    $this->assertResult('http://multifield.example.com/001', 'http://multifield.example.com/002', 'http://multifield.example.com/004', 'http://multifield.example.com/006');
  }

  /**
   * Asserts that arrays are identical.
   */
  protected function assertResult() {
    $assert = [];
    $expected = func_get_args();
    if ($expected && is_array($expected[0])) {
      $expected = $expected[0];
    }
    foreach ($expected as $binary) {
      $assert[$binary] = strval($binary);
    }
    $this->assertIdentical($this->queryResults, $assert);
  }

}

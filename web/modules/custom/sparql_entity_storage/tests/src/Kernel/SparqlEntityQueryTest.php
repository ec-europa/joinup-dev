<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface;
use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests entity query functionality of the SPARQL backend.
 *
 * @see \Drupal\KernelTests\Core\Entity\EntityQueryTest
 *
 * @group sparql_entity_storage
 */
class SparqlEntityQueryTest extends SparqlKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'sparql_field_test',
  ];

  /**
   * A list of query results.
   *
   * @var array
   */
  protected $results;

  /**
   * Dummy reference entities.
   *
   * @var \Drupal\sparql_test\Entity\SparqlTest[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['sparql_field_test']);

    // Create 10 referable entities.
    $prefix = "http://fruit.example.com/";
    for ($i = 0; $i < 10; $i++) {
      $id = sprintf("%s%03d", $prefix, $i + 1);
      $entity = SparqlTest::create([
        'id' => $id,
        'title' => 'fruit title ' . ($i + 1),
        'type' => 'fruit',
        'text' => 'text field ' . ($i % 2),
      ]);
      $entity->save();
      $this->entities[$i] = $entity;
    }

    $prefix = "http://vegetable.example.com/";
    foreach ($this->getTestEntityValues() as $i => $values) {
      $id = sprintf("%s%03d", $prefix, $i + 1);
      $values += [
        'id' => $id,
        'type' => 'vegetable',
      ];
      SparqlTest::create($values)->save();
    }
  }

  /**
   * Tests basic functionality related to ID and bundle filtering.
   */
  public function testIdBundleFilters() {
    // Checks the '=' operator for IDs for a valid ID and a valid bundle.
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/001')
      ->condition('type', 'vegetable')
      ->execute();
    $this->assertResult('http://vegetable.example.com/001');

    // Checks the '=' operator for IDs for a valid ID and a different bundle.
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/001')
      ->condition('type', 'fruit')
      ->execute();
    $this->assertResult();

    // Checks the '!=' operator for the bundle.
    $this->results = $this->getQuery()
      ->condition('id', [
        'http://vegetable.example.com/001',
        'http://fruit.example.com/002',
      ], 'IN')
      ->condition('type', 'vegetable', '!=')
      ->execute();
    $this->assertResult('http://fruit.example.com/002');

    // Checks the IN operator for the bundle.
    $this->results = $this->getQuery()
      ->condition('id', [
        'http://fruit.example.com/002',
        'http://vegetable.example.com/001',
      ], 'IN')
      ->condition('type', ['fruit', 'vegetable'], 'IN')
      ->sort('id')
      ->execute();
    $this->assertResult('http://fruit.example.com/002', 'http://vegetable.example.com/001');

    // Checks the NOT IN operator for the bundle.
    $this->results = $this->getQuery()
      ->condition('id', [
        'http://vegetable.example.com/001',
        'http://fruit.example.com/002',
      ], 'IN')
      ->condition('type', ['vegetable'], 'NOT IN')
      ->execute();
    $this->assertResult('http://fruit.example.com/002');

    // Checks the 'IN' operator for IDs.
    $this->results = $this->getQuery()
      ->condition('id', [
        'http://vegetable.example.com/000',
        'http://vegetable.example.com/001',
        'http://vegetable.example.com/002',
      ], 'IN')
      ->condition('type', 'vegetable')
      ->execute();
    $this->assertResult('http://vegetable.example.com/001', 'http://vegetable.example.com/002');

    // Checks the '=' operator for IDs for an invalid ID.
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/000')
      ->condition('type', 'vegetable')
      ->execute();
    $this->assertResult();

    // Checks the '!=' operator for IDs for a valid ID.
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/001', '!=')
      ->condition('type', 'vegetable')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002', 'http://vegetable.example.com/003', 'http://vegetable.example.com/004', 'http://vegetable.example.com/005', 'http://vegetable.example.com/006', 'http://vegetable.example.com/007', 'http://vegetable.example.com/008', 'http://vegetable.example.com/009', 'http://vegetable.example.com/010');

    // Checks the 'NOT IN' operator for IDs for a valid ID.
    $this->results = $this->getQuery()
      ->condition('id', [
        'http://vegetable.example.com/002',
        'http://vegetable.example.com/003',
        'http://vegetable.example.com/004',
        'http://vegetable.example.com/005',
        'http://vegetable.example.com/006',
        'http://vegetable.example.com/007',
        'http://vegetable.example.com/008',
        'http://vegetable.example.com/009',
        'http://vegetable.example.com/010',
      ], 'NOT IN')
      ->condition('type', 'vegetable')
      ->execute();
    $this->assertResult('http://vegetable.example.com/001');

    // Try to fetch a NULL ID.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The value cannot be NULL for conditions related to the Id and bundle keys.');
    $this->results = $this->getQuery()
      ->condition('id', NULL)
      ->condition('type', 'vegetable')
      ->execute();

    // Try to filter ID with an invalid operator.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Only '=', '!=', '<>', 'IN', 'NOT IN' operators are allowed for the Id and bundle keys.");
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/003', 'LIKE')
      ->condition('type', 'vegetable')
      ->execute();

    // Try to fetch a NULL bundle.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('The value cannot be NULL for conditions related to the Id and bundle keys.');
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/002')
      ->condition('type', NULL)
      ->execute();

    // Try to filter bundle with an invalid operator.
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Only '=', '!=', '<>', 'IN', 'NOT IN' operators are allowed for the Id and bundle keys.");
    $this->results = $this->getQuery()
      ->condition('id', 'http://vegetable.example.com/003')
      ->condition('type', 'multi', 'STARTS WITH')
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
    $this->results = $this->getQuery()
      ->sort('id')
      ->execute();
    $this->assertCount(20, $this->results);

    // Submit an empty 'OR' query.
    $this->results = $this->getQuery('OR')
      ->sort('id')
      ->execute();
    $this->assertCount(20, $this->results);

    $this->results = $this->getQuery()
      ->exists('field_text')
      ->condition("text.format", 'plain_text')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/001');

    $this->results = $this->getQuery()
      ->exists("text.format")
      ->condition('text', '<body>', 'LIKE')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002');

    $this->results = $this->getQuery()
      ->condition('text', '<p>', 'CONTAINS')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002');

    $this->results = $this->getQuery()
      ->condition('text', '<html>', 'STARTS WITH')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002');

    $this->results = $this->getQuery()
      ->condition('text', '</html>', 'ENDS WITH')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002');

    $this->results = $this->getQuery()
      ->condition('text', '</html>', 'ENDS WITH')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002');

    $this->results = $this->getQuery()
      ->condition('reference', $this->entities[6]->id())
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/004', 'http://vegetable.example.com/010');

    $this->results = $this->getQuery()
      ->exists('text.format')
      ->notExists('date')
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/002');

    // OR conjunctions.
    $this->results = $this->getQuery('OR')
      ->exists('text.format')
      ->condition('reference', $this->entities[6]->id())
      ->sort('id')
      ->execute();
    $this->assertResult('http://vegetable.example.com/001', 'http://vegetable.example.com/002', 'http://vegetable.example.com/004', 'http://vegetable.example.com/010');
  }

  /**
   * Tests more complex functionality.
   */
  public function testNestedConditionGroups() {
    $query = $this->getQuery('OR');

    // Entity 001 should match.
    $condition = $query->andConditionGroup();
    $condition->exists('text.format');
    $condition->condition('text.value', 'test', 'CONTAINS');
    $condition->condition('text.format', ['plain_text', 'filtered_html'], 'IN');
    $query->condition($condition);

    // Entity 002 should match.
    $condition = $query->orConditionGroup();
    $condition->condition('text', '<html', 'STARTS WITH');
    $condition->condition('text.value', '/html>', 'ENDS WITH');

    // Entity 006 should match.
    $subcondition = $query->andConditionGroup();
    $subcondition->condition('text_multi', 'test text multi 1');
    $subcondition->condition('text_multi', 'sample string 2', 'CONTAINS');
    $condition->condition($subcondition);

    // Entity 004 should match.
    $subcondition = $query->andConditionGroup();
    $subcondition->condition('reference', $this->entities[4]->id());
    $subcondition->notExists('date');
    $condition->condition($subcondition);
    $query->condition($condition);

    $this->results = $query->sort('id')->execute();
    $this->assertResult('http://vegetable.example.com/001', 'http://vegetable.example.com/002', 'http://vegetable.example.com/004', 'http://vegetable.example.com/006');
  }

  /**
   * Tests sorting and order mechanisms.
   */
  public function testSortOrder() {
    // Sort without direction. Defaults to ASC.
    $this->results = $this->getQuery()
      ->condition('type', 'fruit')
      ->sort('text')
      ->execute();
    $this->assertResult('http://fruit.example.com/001', 'http://fruit.example.com/003', 'http://fruit.example.com/005', 'http://fruit.example.com/007', 'http://fruit.example.com/009', 'http://fruit.example.com/002', 'http://fruit.example.com/004', 'http://fruit.example.com/006', 'http://fruit.example.com/008', 'http://fruit.example.com/010');

    // Sort by ascending direction.
    $this->results = $this->getQuery()
      ->condition('type', 'fruit')
      ->sort('text', 'ASC')
      ->execute();
    $this->assertResult('http://fruit.example.com/001', 'http://fruit.example.com/003', 'http://fruit.example.com/005', 'http://fruit.example.com/007', 'http://fruit.example.com/009', 'http://fruit.example.com/002', 'http://fruit.example.com/004', 'http://fruit.example.com/006', 'http://fruit.example.com/008', 'http://fruit.example.com/010');

    // Sort by descending direction.
    $this->results = $this->getQuery()
      ->condition('type', 'fruit')
      ->sort('text', 'DESC')
      ->execute();
    $this->assertResult('http://fruit.example.com/002', 'http://fruit.example.com/004', 'http://fruit.example.com/006', 'http://fruit.example.com/008', 'http://fruit.example.com/010', 'http://fruit.example.com/001', 'http://fruit.example.com/003', 'http://fruit.example.com/005', 'http://fruit.example.com/007', 'http://fruit.example.com/009');

    // Test multiple property ordering.
    $this->results = $this->getQuery()
      ->condition('type', 'fruit')
      ->sort('text', 'DESC')
      ->sort('id', 'DESC')
      ->execute();
    $this->assertResult('http://fruit.example.com/010', 'http://fruit.example.com/008', 'http://fruit.example.com/006', 'http://fruit.example.com/004', 'http://fruit.example.com/002', 'http://fruit.example.com/009', 'http://fruit.example.com/007', 'http://fruit.example.com/005', 'http://fruit.example.com/003', 'http://fruit.example.com/001');

    $this->results = $this->getQuery()
      ->condition('type', 'fruit')
      ->sort('text')
      ->sort('id', 'DESC')
      ->execute();
    $this->assertResult('http://fruit.example.com/009', 'http://fruit.example.com/007', 'http://fruit.example.com/005', 'http://fruit.example.com/003', 'http://fruit.example.com/001', 'http://fruit.example.com/010', 'http://fruit.example.com/008', 'http://fruit.example.com/006', 'http://fruit.example.com/004', 'http://fruit.example.com/002');

    // Test the bundle key as it is a separate special case along with the id.
    $sub_query = $this->getQuery()->orConditionGroup();
    $sub_query
      ->condition('id', 'http://fruit.example.com/009')
      ->condition('id', 'http://vegetable.example.com/003');

    $this->results = $this->getQuery()
      ->condition($sub_query)
      ->sort('type')
      ->execute();
    $this->assertResult('http://fruit.example.com/009', 'http://vegetable.example.com/003');

    $this->results = $this->getQuery()
      ->condition($sub_query)
      ->sort('type', 'DESC')
      ->execute();
    $this->assertResult('http://vegetable.example.com/003', 'http://fruit.example.com/009');

    // Test sorting using an OR query. Assert that mapping conditions are placed
    // individually.
    $this->results = $this->getQuery('OR')
      ->condition($sub_query)
      ->sort('type', 'DESC')
      ->execute();
    $this->assertResult('http://vegetable.example.com/003', 'http://fruit.example.com/009');

    // Invalid directions are not allowed.
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Only "ASC" and "DESC" are allowed as sort order.');
    $this->results = $this->getQuery()->sort('id', 'SOME_INVALID_DIRECTION');
  }

  /**
   * Tests operators '<', '>', '<=', '>=' for Ids.
   *
   * @dataProvider idStringComparisonDataProvider
   */
  public function testIdStringComparison($value, $operator, $expected_results) {
    $query = $this->getQuery();
    $query->condition('id', $value, $operator);
    $this->results = $query->sort('id')->execute();
    $this->assertResult($expected_results);
  }

  /**
   * Data provider for testIdStringComparison test.
   */
  public function idStringComparisonDataProvider() {
    return [
      [
        'http://fruit.example.com/002',
        '<',
        ['http://fruit.example.com/001'],
      ],
      [
        'http://fruit.example.com/002',
        '<=',
        ['http://fruit.example.com/001', 'http://fruit.example.com/002'],
      ],
      [
        'http://fruit.example.com/009',
        '>',
        // The vegetable bundle entities have an id starting with 'http://m'
        // which is sorted after the 'http://d' so all entities of the
        // vegetable bundled are also returned for the '>' and '>=' operators.
        [
          'http://fruit.example.com/010',
          'http://vegetable.example.com/001',
          'http://vegetable.example.com/002',
          'http://vegetable.example.com/003',
          'http://vegetable.example.com/004',
          'http://vegetable.example.com/005',
          'http://vegetable.example.com/006',
          'http://vegetable.example.com/007',
          'http://vegetable.example.com/008',
          'http://vegetable.example.com/009',
          'http://vegetable.example.com/010',
        ],
      ],
      [
        'http://fruit.example.com/009',
        '>=',
        [
          'http://fruit.example.com/009',
          'http://fruit.example.com/010',
          'http://vegetable.example.com/001',
          'http://vegetable.example.com/002',
          'http://vegetable.example.com/003',
          'http://vegetable.example.com/004',
          'http://vegetable.example.com/005',
          'http://vegetable.example.com/006',
          'http://vegetable.example.com/007',
          'http://vegetable.example.com/008',
          'http://vegetable.example.com/009',
          'http://vegetable.example.com/010',
        ],
      ],
    ];
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
    $this->assertSame($assert, $this->results);
  }

  /**
   * Provides entity values for the test's setup.
   *
   * @return array
   *   An array of entity values.
   */
  protected function getTestEntityValues() {
    $time = $this->container->get('datetime.time')->getRequestTime();
    $return = [];
    // Entity 001.
    $return[] = [
      'text' => [
        'value' => 'test text 1',
        'format' => 'plain_text',
      ],
      'text_multi' => [
        'test text multi 1',
      ],
      'date' => $time,
      'reference' => [
        $this->entities[4]->id(),
      ],
    ];

    // Entity 002.
    $return[] = [
      'text' => [
        'value' => '<html><body><p>Hello world!</p></body></html>',
        'format' => 'full_html',
      ],
      'text_multi' => [
        'test text multi 1',
      ],
    ];

    // Entity 003.
    $return[] = [
      'text' => 'test text 1',
      'text_multi' => [
        'test text multi 2',
      ],
      'date' => $time,
      'reference' => [
        $this->entities[2]->id(),
        $this->entities[3]->id(),
        $this->entities[4]->id(),
      ],
    ];

    // Entity 004.
    $return[] = [
      'reference' => [
        $this->entities[0]->id(),
        $this->entities[1]->id(),
        $this->entities[2]->id(),
        $this->entities[3]->id(),
        $this->entities[4]->id(),
        $this->entities[5]->id(),
        $this->entities[6]->id(),
        $this->entities[7]->id(),
        $this->entities[8]->id(),
        $this->entities[9]->id(),
      ],
    ];

    // Entity 005.
    $return[] = [
      'text' => 'sample string 2',
      'text_multi' => [
        'test text multi 1',
        'test text multi 3',
        'test text multi 2',
      ],
    ];

    // Entity 006.
    $return[] = [
      'text' => 'sample string 1',
      'text_multi' => [
        'test text multi 1',
        'sample string 2',
      ],
      'reference' => [
        $this->entities[8]->id(),
        $this->entities[9]->id(),
      ],
    ];

    // Entity 007.
    $return[] = [
      'text' => 'test text 1',
      'text_multi' => [
        'test text multi 1',
      ],
      'date' => $time,
      'reference' => [
        $this->entities[5]->id(),
      ],
    ];

    // Entity 008.
    $return[] = [
      'text' => 'test text 1',
      'text_multi' => [
        'test text multi 1',
      ],
      'date' => $time,
      'reference' => [
        $this->entities[8]->id(),
      ],
    ];

    // Entity 009.
    $return[] = [
      'text' => 'test text 1',
      'text_multi' => [
        'test text multi 1',
      ],
      'date' => $time,
      'reference' => [
        $this->entities[1]->id(),
      ],
    ];

    // Entity 010.
    $return[] = [
      'text' => 'test text 1',
      'text_multi' => [
        'test text multi 1',
      ],
      'date' => $time,
      'reference' => [
        $this->entities[6]->id(),
      ],
    ];

    return $return;
  }

  /**
   * Returns the SPARQL entity query.
   *
   * @param string $operator
   *   (optional) The logic operator ('AND' or 'OR'). Defaults to 'AND'.
   *
   * @return \Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlQueryInterface
   *   The SPARQL entity query.
   */
  protected function getQuery(string $operator = 'AND'): SparqlQueryInterface {
    return $this->container->get('entity_type.manager')
      ->getStorage('sparql_test')
      ->getQuery($operator);
  }

}

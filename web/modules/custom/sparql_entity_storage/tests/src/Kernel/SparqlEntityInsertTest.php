<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests entity query functionality of the SPARQL backend.
 *
 * @group sparql_entity_storage
 */
class SparqlEntityInsertTest extends SparqlKernelTestBase {

  /**
   * Field names.
   *
   * @var string[]
   */
  const FIELDS = [
    'title',
    'type',
    'reference',
    'date',
    'text',
    'text_multi',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'sparql_field_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['sparql_field_test']);
  }

  /**
   * Test entity create.
   *
   * @dataProvider providerTestEntityInsertCallback
   */
  public function testEntityInsert($values): void {
    // Create 10 referable entities.
    for ($i = 0; $i < 10; $i++) {
      $entity = SparqlTest::create([
        'title' => $this->randomString(),
        'type' => 'vegetable',
        'text' => $this->randomString(),
      ]);
      $entity->save();
      $entities[$i] = $entity;
    }

    $referable_entities = array_rand($entities, 4);
    $reference_array = [];
    foreach ($referable_entities as $index) {
      $reference_array[] = $entities[$index]->id();
    }

    $values += [
      'title' => $this->randomMachineName(),
      'type' => 'vegetable',
      'reference' => $reference_array,
      'date' => $this->container->get('datetime.time')->getRequestTime(),
    ];

    $entity = SparqlTest::create($values);
    $entity->save();

    // Reload the entity.
    $loaded_entity = SparqlTest::load($entity->id());
    foreach (static::FIELDS as $field_name) {
      $this->assertEquals($this->getEntityValue($entity, $field_name), $this->getEntityValue($loaded_entity, $field_name));
    }
  }

  /**
   * Data provider for testEntityInsert().
   *
   * @return array[]
   *   Test cases.
   */
  public static function providerTestEntityInsertCallback(): array {
    $random = new Random();
    return [
      [
        [
          'text' => $random->string(),
          'text_multi' => [
            $random->string(3000),
            $random->string(3000),
          ],
        ],
      ],
      [
        [
          'text' => $random->string(3000),
          'text_multi' => [
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
            $random->string(3000),
          ],
        ],
      ],
      // Empty values to test that values are not saved.
      [[]],
    ];
  }

  /**
   * Returns the value of a field name of an array.
   *
   * Since SPARQL does not support deltas yet, this method will sort the values
   * so that they can be comparable.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   * @param string $field_name
   *   The field name.
   *
   * @return mixed
   *   The retrieved value or NULL if no value exists.
   *
   * @todo Remove this when the deltas are supported.
   * @todo Discuss whether we need this in the storage level.
   */
  protected function getEntityValue(ContentEntityInterface $entity, string $field_name) {
    $value = $entity->get($field_name)->getValue();
    if (empty($value)) {
      return $value;
    }
    return asort($value);
  }

}

<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Tests\joinup_core\Kernel\JoinupKernelTestBase;

/**
 * Tests Entity Query functionality of the Sparql backend.
 *
 * This is based on
 * @see \Drupal\KernelTests\Core\Entity\EntityQueryTest
 *
 * @group Entity
 */
class SparqlEntityInsertTest extends JoinupKernelTestBase {

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
   * Dummy reference entities.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $dummyEntities;

  protected function setUp() {
    parent::setUp();

    // Create 10 referable entities.
    for ($i = 0; $i < 10; $i++) {
      $dummy = Rdf::create([
        'label' => $this->randomString(),
        'rid' => 'dummy',
        'field_text' => $this->randomString(),
      ]);
      $dummy->save();
      $this->dummyEntities[$i] = $dummy;
    }
  }

  /**
   * Returns the value of a field name of an array.
   *
   * Since rdf does not support deltas yet, this method will sort the values so
   * that they can be comparable.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *    The entity object.
   * @param $field_name
   *    The field name.
   *
   * @return mixed
   *    The retrieved value or NULL if no value exists.
   *
   * @todo: Remove this when the deltas are supported.
   * @todo: Discuss whether we need this in the storage level.
   */
  protected function getEntityValue(RdfInterface $entity, $field_name) {
    $value = $entity->get($field_name)->getValue();
    if (empty($value)) {
      return $value;
    }
    return asort($value);
  }

  /**
   * Test entity create.
   * @dataProvider providerTestEntityInsertCallback
   */
  public function testEntityInsert($values) {
    $bundle = 'multifield';
    $referable_entities = array_rand($this->dummyEntities, 4);
    $reference_array = [];
    foreach ($referable_entities as $index) {
      $reference_array[] = $this->dummyEntities[$index]->id();
    }

    $values += [
      'label' => $this->randomMachineName(),
      'rid' => $bundle,
      'field_reference' => $reference_array,
      'field_date' => \Drupal::time()->getRequestTime(),
    ];

    $entity = Rdf::create($values);
    $entity->save();
    $this->assertTrue($entity instanceof RdfInterface);

    // Load the entity.
    $loaded_entity = $this->entityManager->getStorage('rdf_entity')->loadUnchanged($entity->id());
    $this->assertTrue($loaded_entity instanceof RdfInterface);
    foreach (['label', 'rid', 'field_reference', 'field_date', 'field_text', 'field_text_multi'] as $field_name) {
      $this->assertEquals($this->getEntityValue($entity, $field_name), $this->getEntityValue($loaded_entity, $field_name));
    }
  }

  /**
   * Data provider for testEntityInsert().
   */
  public static function providerTestEntityInsertCallback() {
    $random = new Random();
    return [
      [
        [
          'field_text' => $random->string(),
          'field_text_multi' => [
            $random->string(3000),
            $random->string(3000),
          ],
        ],
      ],
      [
        [
          'field_text' => $random->string(3000),
          'field_text_multi' => [
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

}

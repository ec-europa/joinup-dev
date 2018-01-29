<?php

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\rdf_entity\Entity\Rdf;

/**
 * Base class for tests that verify the validation of rdf entities.
 */
abstract class RdfEntityValidationTestBase extends JoinupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'allowed_formats',
    'cached_computed_field',
    'image',
    'joinup_core',
    'link',
    'node',
    'og',
    'piwik_reporting_api',
    'rdf_taxonomy',
    'state_machine',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('joinup_core');
  }

  /**
   * Tests that the required fields throw a validation error when left empty.
   */
  public function testRequiredFields(): void {
    $entity = $this->createEntity();
    $violations = $entity->validate();

    $required_fields = $this->getRequiredFields();
    $this->assertSameSize($required_fields, $violations);
    foreach ($violations as $violation) {
      $this->assertContains($violation->getPropertyPath(), $required_fields);
      $this->assertEquals($violation->getMessage(), 'This value should not be null.');
    }
  }

  /**
   * Creates an rdf entity of the bundle the test covers.
   *
   * @param array $values
   *   An array of values to set, keyed by property name. Optional.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The rdf entity object.
   */
  protected function createEntity(array $values = []): Rdf {
    return Rdf::create(['rid' => $this->bundle()] + $values);
  }

  /**
   * Returns the required fields for the bundle being tested.
   *
   * A field is required when its value cannot be null.
   *
   * @return array
   *   A list of required fields.
   */
  abstract protected function getRequiredFields(): array;

  /**
   * Returns the ID of the bundle the test covers.
   *
   * @return string
   *   The bundle ID.
   */
  abstract protected function bundle(): string;

}

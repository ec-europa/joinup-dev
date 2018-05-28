<?php

/**
 * @file
 * Contains \RdfEntityProvenanceSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\rdf_entity\RdfInterface;

/**
 * Behat step definitions for testing provenance activities for rdf entities.
 */
class RdfEntityProvenanceSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityTrait;
  use RdfEntityTrait;
  use UtilityTrait;
  use TraversingTrait;

  /**
   * Test provenance entities.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $provenanceActivities = [];

  /**
   * Creates a number of provenance activities with data provided in a table.
   *
   * Table format:
   *
   * @codingStandardsIgnoreStart
   * | started   | associated with    | entity              | enabled |
   * | 123456789 | http://example.com | Related entity name | 1|0     |
   * @codingStandardsIgnoreEnd
   *
   * Only the entity field is required. The 'started' timestamp will be filled
   * with the current timestamp if left empty. The 'enabled' flag is by default
   * TRUE.
   *
   * @param \Behat\Gherkin\Node\TableNode $data_table
   *   The provenance entity data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )provenance activities:
   */
  public function givenProvenanceRecords(TableNode $data_table): void {
    $aliases = self::provenanceFieldAliases();

    foreach ($data_table->getColumnsHash() as $entity) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($entity as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in the provenance data table.");
        }
      };

      if (empty($values['provenance_entity'])) {
        throw new \Exception('The provenance entity is required.');
      }

      // Convert user friendly values to machine values.
      $values = $this->convertValueAliases($values);

      // Provide default values.
      $values = $this->provideDefaultValues($values);

      $this->createActivity($values);
    }
  }

  /**
   * Checks the number of available provenance activities.
   *
   * @param int $number
   *   The expected number of activities.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number provenance activity
   * @Then I should have :number provenance activities
   */
  public function assertActivitiesCount(int $number): void {
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', 'provenance_activity')
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of activities. Expected number: $number, actual number: $actual.");
    }
  }

  /**
   * Asserts that an rdf entity has a provenance activity related.
   *
   * @param string $entity_label
   *   The entity label that will be checked that has a provenance activity
   *   related.
   * @param string $entity_type
   *   The entity bundle of the entity that will be checked for having a
   *   provenance activity.
   *
   * @throws \Exception
   *   Thrown when an entity does not have a provenance activity record related.
   *
   * @Then the :entity_label :entity_type should have a provenance activity related
   */
  public function assertActivityRelatedToEntity(string $entity_label, string $entity_type): void {
    $entity_type = str_replace(' ', '_', $entity_type);
    $rdf_entity = $this->getRdfEntityByLabel($entity_label, $entity_type);
    /** @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $provenance_helper */
    $provenance_helper = \Drupal::service('rdf_entity_provenance.provenance_helper');
    $activity = $provenance_helper->getProvenanceByReferredEntity($rdf_entity->id());
    if (empty($activity)) {
      throw new \Exception("The entity labeled {$rdf_entity->label()} does not have a provenance activity related.");
    }
  }

  /**
   * Asserts that a list of fields are disabled.
   *
   * @param string $fields
   *   A list of comma separated field labels.
   *
   * @throws \Exception
   *   Thrown when a field is not found by name or is not disabled.
   *
   * @Then the following fields should be disabled :fields
   */
  public function givenDisabledFields(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $found = [];
    foreach ($fields as $field) {
      $element = $this->findDisabledField($field);
      if (empty($element)) {
        $found[] = $field;
      }
    }

    if (!empty($found)) {
      throw new Exception('The following fields were not found or were enabled: ' . implode(', ', $found));
    }
  }

  /**
   * Asserts that a list of fields are not disabled.
   *
   * @param string $fields
   *   A list of comma separated field labels.
   *
   * @throws \Exception
   *   Thrown when a field is not found by name or is disabled.
   *
   * @Then the following fields should not be disabled :fields
   */
  public function givenNotDisabledFields(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $found = [];
    foreach ($fields as $field) {
      $element = $this->findDisabledField($field);
      if (!empty($element)) {
        $found[] = $field;
      }
    }

    if (!empty($found)) {
      throw new Exception('The following fields were disabled: ' . implode(', ', $found));
    }

  }

  /**
   * Returns the field alias mapping.
   *
   * @return array
   *   The field alias mapping.
   */
  protected static function provenanceFieldAliases(): array {
    // Mapping alias - field name.
    return [
      'started' => 'provenance_started',
      'associated with' => 'provenance_associated_with',
      'entity' => 'provenance_entity',
      'enabled' => 'provenance_enabled',
    ];
  }

  /**
   * Enriches the provided field values with defaults for missing values.
   *
   * @param array $fields
   *   An array of fields keyed by field name.
   *
   * @return array
   *   The array with default values added.
   */
  protected function provideDefaultValues(array $fields): array {
    $default_values = [
      'provenance_started' => \Drupal::time()->getRequestTime(),
      'provenance_enabled' => TRUE,
    ];

    $fields += $default_values;
    return $fields;
  }

  /**
   * Converts values from user friendly to normal machine values.
   *
   * @param array $fields
   *   An array of fields keyed by field name.
   *
   * @return mixed
   *   The array with the values converted.
   *
   * @throws \Exception
   *    Throws an exception when a mapped value is not found.
   */
  protected function convertValueAliases(array $fields): array {
    $entity_label = $fields['provenance_entity'];
    $entity = $this->getEntityByLabel('rdf_entity', $entity_label);
    if (empty($entity)) {
      throw new \Exception("Entity with entity label '${entity_label}' was not found.");
    }

    $fields['provenance_entity'] = $entity->id();
    return $fields;
  }

  /**
   * Creates a provenance entity from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   A new provenance entity.
   */
  protected function createActivity(array $values): RdfInterface {
    $activity = $this->createRdfEntity('provenance_activity', $values);
    $this->provenanceActivities[$activity->id()] = $activity;
    return $activity;
  }

  /**
   * Deletes a provenance activity.
   *
   * @param string $entity_label
   *   The title of the provenance entity (the entity related to the provenance
   *   activity).
   * @param string $entity_bundle
   *   The bundle of the provenance entity.
   *
   * @throws \Exception
   *   Thrown when the entity is not found or there is no provenance activity
   *   for that entity.
   *
   * @When (I )delete the provenance activity of the :entity :entity_bundle
   */
  public function deleteActivity(string $entity_label, string $entity_bundle): void {
    $entity = $this->getEntityByLabel($entity_label, 'rdf_entity', $entity_bundle);
    if (empty($entity)) {
      throw new Exception("Entity '${entity_label}' was not found.");
    }

    /** @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $provenance_helper */
    $provenance_helper = \Drupal::service('rdf_entity_provenance.provenance_helper');
    $activity = $provenance_helper->getProvenanceByReferredEntity($entity->id());
    if (empty($activity)) {
      if (empty($entity_id)) {
        throw new Exception("Entity '${entity_label}' does not have a provenance activity.");
      }
    }

    $activity->delete();
  }

  /**
   * Removes any created activities.
   *
   * @AfterScenario
   */
  public function cleanActivities(): void {
    // Remove any collections that were created.
    foreach ($this->provenanceActivities as $activity) {
      $activity->delete();
    }
  }

}

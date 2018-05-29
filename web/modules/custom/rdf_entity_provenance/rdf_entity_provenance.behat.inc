<?php

/**
 * @file
 * Contains \RdfEntityProvenanceSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing provenance activities for rdf entities.
 */
class RdfEntityProvenanceSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityTrait;
  use RdfEntityTrait;
  use EntityReferenceTrait;

  /**
   * Test provenance entities.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $provenanceActivities = [];

  /**
   * Creates a number of provenance activity entities from a table.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | entity                          | associated with              | enabled | author | started          |
   * | The Great Solution              | http://example.com/publisher | yes     | joe    | 28-01-1995 12:06 |
   * | http://example.com/distribution | http://example.com           | no      | donna  | 03-07-2012 03:54 |
   * @codingStandardsIgnoreEnd
   *
   * Only the 'entity' field is required. The 'entity' field could be passed
   * either as an entity label for existing entities or as the entity ID for
   * provenance activities referring not imported entities.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The collection data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )provenance activities:
   */
  public function givenProvenanceActivities(TableNode $table): void {
    $aliases = self::provenanceFieldAliases();

    foreach ($table->getColumnsHash() as $activity) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($activity as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in table.");
        }
      };

      // Convert entity label to ID, if case.
      if (!UrlHelper::isValid($values['provenance_entity'], TRUE)) {
        $values['provenance_entity'] = $this->getRdfEntityByLabel($values['provenance_entity'])->id();
      }

      // Transform the date into a timestamp.
      if (array_key_exists('provenance_started', $values)) {
        $values['provenance_started'] = strtotime($values['provenance_started']);
      }

      // Convert user friendly values to machine values.
      if (array_key_exists('provenance_enabled', $values)) {
        $values['provenance_enabled'] = strtolower($values['provenance_enabled']);
        if (!in_array($values['provenance_enabled'], ['yes', 'no'])) {
          throw new \Exception("Value '{$values['provenance_enabled']}' is not an acceptable value for field 'provenance_enabled'.");
        }
        $values['provenance_enabled'] = $values['provenance_enabled'] === 'yes';
      }

      // Convert any entity reference field label value with the entity ID.
      $values = $this->convertEntityReferencesValues('rdf_entity', 'provenance_activity', $this->parseRdfEntityFields($values));

      // Add the bundle.
      $values['rid'] = 'provenance_activity';

      $activity = Rdf::create($values);
      $activity->save();
      $this->provenanceActivities[$activity->id()] = $activity;
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
   * @param string $entity_type_id
   *   The entity bundle of the entity that will be checked for having a
   *   provenance activity.
   *
   * @throws \Exception
   *   Thrown when an entity does not have a provenance activity record related.
   *
   * @Then the :entity_label :entity_type should have a provenance activity related
   */
  public function assertActivityRelatedToEntity(string $entity_label, string $entity_type_id): void {
    $entity_type_id = str_replace(' ', '_', $entity_type_id);
    $rdf_entity = $this->getRdfEntityByLabel($entity_label, $entity_type_id);
    /** @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $provenance_helper */
    $provenance_helper = \Drupal::service('rdf_entity_provenance.provenance_helper');
    $activity = $provenance_helper->getProvenanceByReferredEntity($rdf_entity->id());
    if (empty($activity)) {
      throw new \Exception("The entity labeled {$rdf_entity->label()} does not have a provenance activity related.");
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
      'author' => 'uid',
      'enabled' => 'provenance_enabled',
    ];
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
      throw new \Exception("Entity '${entity_label}' was not found.");
    }

    /** @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $provenance_helper */
    $provenance_helper = \Drupal::service('rdf_entity_provenance.provenance_helper');
    $activity = $provenance_helper->loadProvenanceActivity($entity->id());
    if (empty($activity)) {
      throw new \Exception("Entity '${entity_label}' does not have a provenance activity.");
    }

    $activity->delete();
  }

  /**
   * Removes any created activities.
   *
   * @AfterScenario
   */
  public function cleanActivities(): void {
    \Drupal::entityTypeManager()->getStorage('rdf_entity')->delete($this->provenanceActivities);
  }

}

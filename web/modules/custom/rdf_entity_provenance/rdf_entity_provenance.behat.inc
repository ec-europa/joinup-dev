<?php

/**
 * @file
 * Contains \RdfEntityProvenanceSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ExpectationException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;

/**
 * Behat step definitions for testing provenance activities for RDF entities.
 */
class RdfEntityProvenanceSubContext extends DrupalSubContextBase {

  use EntityReferenceTrait;
  use EntityTrait;
  use RdfEntityTrait;

  /**
   * Provenance activity RDF entities used in test.
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

      if (array_key_exists('provenance_associated_with', $values)) {
        if (!UrlHelper::isValid($values['provenance_associated_with'], TRUE)) {
          $values['provenance_associated_with'] = $this->getEntityIdByLabel($values['provenance_associated_with'], 'rdf_entity');
        }
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
   * Returns a 'provenance_activity' entity given a referral.
   *
   * @param string $referral
   *   The entity label or ID for existing entities, or the ID as URI for
   *   provenance activities referring not imported entities.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The 'provenance_activity' RDF entity.
   *
   * @throws \Exception
   *   If $referral was passed as entity label and that entity doesn't exist.
   */
  protected function getActivityForReferral(string $referral): ?RdfInterface {
    $id = !UrlHelper::isValid($referral, TRUE) ? $this->getRdfEntityByLabel($referral)->id() : $referral;
    /** @var \Drupal\rdf_entity_provenance\ProvenanceHelperInterface $helper */
    $helper = \Drupal::service('rdf_entity_provenance.provenance_helper');
    $activity = $helper->loadProvenanceActivity($id);
    if (empty($activity)) {
      return NULL;
    }

    // Deal with the Behat static cache invalidation issue.
    /** @var \Drupal\rdf_entity\RdfInterface $return */
    $return = \Drupal::entityTypeManager()->getStorage('rdf_entity')->loadUnchanged($activity->id());
    return $return;
  }

  /**
   * Asserts that a 'provenance_activity' entity exists, given a referral.
   *
   * @param string $referral
   *   The entity label or ID for existing entities, or the ID as URI for
   *   provenance activities referring not imported entities.
   *
   * @throws \Exception
   *   If $referral was passed as entity label and that entity doesn't exist or
   *   there's no provenance activity recorded for the referred entity.
   *
   * @Then (the ):referral (entity )should have a related provenance activity
   */
  public function assertActivityForReferral(string $referral): void {
    if (!$this->getActivityForReferral($referral)) {
      throw new \Exception("There's no provenance activity for '$referral'.");
    }
  }

  /**
   * Asserts that a 'provenance_activity' entity does not exist.
   *
   * @param string $referral
   *   The entity label or ID for existing entities, or the ID as URI for
   *   provenance activities referring not imported entities.
   *
   * @throws \Exception
   *   If $referral was passed as entity label and that entity doesn't exist or
   *   there is a provenance activity recorded for the referred entity.
   *
   * @Then (the ):referral (entity )should not have a related provenance activity
   */
  public function assertNoActivityForReferral(string $referral): void {
    if ($this->getActivityForReferral($referral)) {
      throw new \Exception("A provenance activity for '$referral' was found but should have not.");
    }
  }

  /**
   * Asserts that a given entity is on the federation blacklist.
   *
   * If $referral exists as RDF entity, you could pass it as an entity label,
   * otherwise pass the entity ID as URI.
   *
   * @param string $referral
   *   The entity label or ID for existing entities, or the ID as URI for
   *   provenance activities referring not imported entities.
   *
   * @throws \Exception
   *   If $referral was passed as entity label and that entity doesn't exist or
   *   there's no provenance activity recorded for the referred entity.
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the referred entity is not blacklisted for federation.
   *
   * @Then the :referral( entity) is blacklisted for federation
   */
  public function assertEntityIsBlacklistedFromFederation(string $referral): void {
    if ($this->getActivityForReferral($referral)->get('provenance_enabled')->value) {
      throw new ExpectationException("Entity '$referral' should be blacklisted for federation but it's not.", $this->getSession()->getDriver());
    }
  }

  /**
   * Asserts that a given entity is not on the federation blacklist.
   *
   * If $referral exists as RDF entity, you could pass it as entity label,
   * otherwise pass the entity ID as URI.
   *
   * @param string $referral
   *   The entity label or ID for existing entities, or the ID as URI for
   *   provenance activities referring not imported entities.
   *
   * @throws \Exception
   *   If $referral was passed as entity label and that entity doesn't exist or
   *   there's no provenance activity recorded for the referred entity.
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the referred entity is blacklisted for federation.
   *
   * @Then the :referral( entity) is not blacklisted for federation
   */
  public function assertEntityIsNotBlacklistedFromFederation(string $referral): void {
    if (!$this->getActivityForReferral($referral)->get('provenance_enabled')->value) {
      throw new ExpectationException("Entity '$referral' should not be blacklisted for federation but it is.", $this->getSession()->getDriver());
    }
  }

  /**
   * Deletes a provenance activity given an entity label or ID.
   *
   * If $referral exists as RDF entity, you could pass it as entity label,
   * otherwise pass the entity ID as URI.
   *
   * @param string $referral
   *   The entity label or ID for existing entities, or the ID as URI for
   *   provenance activities referring not imported entities.
   *
   * @throws \Exception
   *   If $referral was passed as entity label and that entity doesn't exist or
   *   there's no provenance activity recorded for the referred entity.
   *
   * @Given I delete the provenance activity of :referral( entity)
   */
  public function deleteProvenanceActivity(string $referral): void {
    $this->getActivityForReferral($referral)->delete();
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
   * Returns the field alias mapping.
   *
   * @return string[]
   *   The field alias mapping.
   */
  protected static function provenanceFieldAliases(): array {
    return [
      'entity' => 'provenance_entity',
      'associated with' => 'provenance_associated_with',
      'enabled' => 'provenance_enabled',
      'author' => 'uid',
      'started' => 'provenance_started',
    ];
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

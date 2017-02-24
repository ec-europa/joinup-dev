<?php

/**
 * @file
 * Contains \ContactInformationSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing contact information entities.
 */
class ContactInformationSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test contact information rdf entities.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $contactInformations = [];

  /**
   * Navigates to the canonical page display of a contact information entity.
   *
   * @param string $label
   *   The label of the contact information entity.
   *
   * @When (I )go to the :label contact information page
   * @When (I )visit the :label contact information page
   */
  public function visitContactInformationPage($label) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getRdfEntityByLabel($label, 'contact_information');
    $this->visitPath($entity->url());
  }

  /**
   * Creates a contact information entity with data provided in a table.
   *
   * Table format:
   * | email | foo@bar.com, baz@qux.com |
   * | name  | Jack Smith               |
   *
   * @param TableNode $contact_table
   *   The contact table.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )contact:
   */
  public function givenContactEntity(TableNode $contact_table) {
    $aliases = self::contactInformationFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($contact_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in contact information table.");
      }
    };

    $this->createContactInformation($values);
  }

  /**
   * Creates a number of contact information with data provided in a table.
   *
   * Table format:
   * | email                            | name       | author      |
   * | foo@example.com, bar@example.com | John Irwin | Author name |
   * | baz@example.com                  | Jack Smith |             |
   *
   * @param TableNode $contact_table
   *   The contacts table.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )contacts:
   */
  public function givenContactEntities(TableNode $contact_table) {
    $aliases = self::contactInformationFieldAliases();

    foreach ($contact_table->getColumnsHash() as $entity) {
      $values = [];

      foreach ($entity as $key => $value) {
        // Replace aliases with the actual field names.
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in contact information table.");
        }
      }

      $this->createContactInformation($values);
    }
  }

  /**
   * Checks the number of available contact information entities.
   *
   * @param int $number
   *   The expected number of entities.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number contact information(s)
   */
  public function assertContactInformationCount($number) {
    $this->assertRdfEntityCount($number, 'contact_information');
  }

  /**
   * Deletes an contact information entity.
   *
   * @param string $name
   *   The name of the contact information to delete.
   *
   * @When I delete the :contact contact information
   */
  public function deleteContactInformation($name) {
    $this->getRdfEntityByLabel($name, 'contact_information')->delete();
  }

  /**
   * Remove any created contact information entities.
   *
   * @AfterScenario
   */
  public function cleanContactInformationEntities() {
    foreach ($this->contactInformations as $entity) {
      $entity->delete();
    }
  }

  /**
   * Creates a contact information from the given property and field data.
   *
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new contact information entity.
   *
   * @throws \Exception
   *   When the author is specified but the related user doesn't exist.
   */
  protected function createContactInformation(array $values) {
    $values['rid'] = 'contact_information';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();

    // The 'author' key was replaced by 'uid' in the calling function.
    if (!empty($values['uid'])) {
      $values['uid'] = $this->getUserByName($values['uid'])->id();
    }

    $entity = Rdf::create($values);
    $entity->save();
    $this->contactInformations[$entity->id()] = $entity;

    return $entity;
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function contactInformationFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'email' => 'field_ci_email',
      'name' => 'field_ci_name',
      'Website URL' => 'field_ci_webpage',
      'author' => 'uid',
    ];
  }

}

<?php

/**
 * @file
 * Contains \OwnerSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing owners.
 */
class OwnerSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test owner rdf entities.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $owners = [];

  /**
   * Creates a person owner with data provided in a table.
   *
   * Table format:
   * | name | Mightily Oats |
   *
   * @param TableNode $person_table
   *   The person table.
   *
   * @Given (the following )person:
   */
  public function givenPerson(TableNode $person_table) {
    // Convert aliased field names.
    $values = $this->convertFieldAliases('person', $person_table->getRowsHash());

    $this->createOwner('person', $values);
  }

  /**
   * Creates a number of person owners with data provided in a table.
   *
   * Table format:
   * | name          |
   * | Mightily Oats |
   * | John Irwin    |
   *
   * @param TableNode $person_table
   *   The person table.
   *
   * @Given (the following )persons:
   */
  public function givenPersons(TableNode $person_table) {
    foreach ($person_table->getColumnsHash() as $person) {
      // Convert aliased field names.
      $values = $this->convertFieldAliases('person', $person);

      $this->createOwner('person', $values);
    }
  }

  /**
   * Creates a organization owner with data provided in a table.
   *
   * Table format:
   * | name | Red birds |
   *
   * @param TableNode $organisation_table
   *   The organisation table.
   *
   * @Given (the following )organization:
   */
  public function givenOrganization(TableNode $organisation_table) {
    // Convert aliased field names.
    $values = $this->convertFieldAliases('organization', $organisation_table->getRowsHash());

    $this->createOwner('organization', $values);
  }

  /**
   * Creates a number of organisation owners with data provided in a table.
   *
   * Table format:
   * | name      |
   * | Red birds |
   * | Acme Inc. |
   *
   * @param TableNode $organisation_table
   *   The organisation table.
   *
   * @Given (the following )organisations:
   */
  public function givenOrganisations(TableNode $organisation_table) {
    foreach ($organisation_table->getColumnsHash() as $organisation) {
      // Convert aliased field names.
      $values = $this->convertFieldAliases('person', $organisation);

      $this->createOwner('organization', $values);
    }
  }

  /**
   * Checks the number of available persons.
   *
   * @param int $number
   *   The expected number of persons.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number person(s)
   */
  public function assertPersonCount($number) {
    $this->assertRdfEntityCount($number, 'person');
  }

  /**
   * Checks the number of available organizations.
   *
   * @param int $number
   *   The expected number of organizations.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number organization(s)
   */
  public function assertOrganizationCount($number) {
    $this->assertRdfEntityCount($number, 'organization');
  }

  /**
   * Deletes an owner entity of type person.
   *
   * @param string $name
   *   The name of the person to delete.
   *
   * @When I delete the :owner person
   */
  public function deletePerson($name) {
    $this->getRdfEntityByLabel($name, 'person')->delete();
  }

  /**
   * Deletes an owner entity of type organization.
   *
   * @param string $name
   *   The name of the organization to delete.
   *
   * @When I delete the :owner organization
   */
  public function deleteOrganization($name) {
    $this->getRdfEntityByLabel($name, 'organization')->delete();
  }

  /**
   * Remove any created owner entities.
   *
   * @AfterScenario
   */
  public function cleanOwners() {
    // Remove any owners that were created.
    foreach ($this->owners as $owner) {
      $owner->delete();
    }
  }

  /**
   * Converts aliased to real field names.
   *
   * @param string $type
   *   The type of the owner.
   * @param array $aliased_values
   *   An array of values keyed by field alias.
   *
   * @return array
   *   The field values keyed by real field name.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   */
  protected function convertFieldAliases($type, array $aliased_values) {
    $aliases = self::ownerFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($aliased_values as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in $type table.");
      }
    }

    return $values;
  }

  /**
   * Creates an owner from the given property and field data.
   *
   * @param string $type
   *   The type of the owner.
   * @param array $values
   *   An optional associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new owner entity of the specified type.
   */
  protected function createOwner($type, array $values) {
    $values['rid'] = $type;
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();

    $owner = Rdf::create($values);
    $owner->save();
    $this->owners[$owner->id()] = $owner;

    return $owner;
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *    Mapping.
   */
  protected static function ownerFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'name' => 'label',
    ];
  }

}

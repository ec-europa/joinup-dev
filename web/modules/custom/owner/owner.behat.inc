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
   * @param TableNode $collection_table
   *   The collection data.
   *
   * @Given (the following )person:
   */
  public function givenPerson(TableNode $collection_table) {
    // Convert aliased field names.
    $values = $this->convertFieldAliases('person', $collection_table);

    $this->createOwner('person', $values);
  }

  /**
   * Creates a organisation owner with data provided in a table.
   *
   * Table format:
   * | name | Red birds |
   *
   * @param TableNode $collection_table
   *   The collection data.
   *
   * @Given (the following )organisation:
   */
  public function givenOrganisation(TableNode $collection_table) {
    // Convert aliased field names.
    $values = $this->convertFieldAliases('organisation', $collection_table);

    $this->createOwner('organisation', $values);
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
   * Checks the number of available organisations.
   *
   * @param int $number
   *   The expected number of organisations.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number organisation(s)
   */
  public function assertOrganisationCount($number) {
    $this->assertRdfEntityCount($number, 'organisation');
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
   * Deletes an owner entity of type organisation.
   *
   * @param string $name
   *   The name of the organisation to delete.
   *
   * @When I delete the :owner organisation
   */
  public function deleteOrganisation($name) {
    $this->getRdfEntityByLabel($name, 'organisation')->delete();
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
   * @param TableNode $collection_table
   *   The collection data.
   *
   * @return array
   *   The field values keyed by real field name.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   */
  protected function convertFieldAliases($type, TableNode $collection_table) {
    $aliases = self::ownerFieldAliases($type);

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($collection_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in $type table.");
      }
    };

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
   * @param string $bundle
   *   The bundle for which to retrieve the mappings.
   *
   * @return array
   *    Mapping.
   *
   * @throws \Exception
   *   Thrown the bundle is unknown.
   */
  protected static function ownerFieldAliases($bundle) {
    // Mapping alias - field name.
    $map = [
      'organisation' => [
        'uri' => 'id',
        'name' => 'field_oo_name',
      ],
      'person' => [
        'uri' => 'id',
        'name' => 'field_op_name',
      ],
    ];

    if (!isset($map[$bundle])) {
      throw new \Exception("Unknown field aliases for bundle $bundle.");
    }

    return $map[$bundle];
  }

}

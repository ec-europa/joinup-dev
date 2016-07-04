<?php

/**
 * @file
 * Contains \ContactInformationSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing contact information entities.
 */
class ContactInformationSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test contact information rdf entities.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $contactInformations = [];

  /**
   * Creates a contact information entity with data provided in a table.
   *
   * Table format:
   * | email | foo@bar.com, baz@qux.com |
   * | name  | Jack Smith               |
   *
   * @param TableNode $collection_table
   *   The collection data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )contact information:
   */
  public function givenContactInformation(TableNode $collection_table) {
    $aliases = self::contactInformationFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($collection_table->getRowsHash() as $key => $value) {
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
   */
  protected function createContactInformation(array $values) {
    $values['rid'] = 'contact_information';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();

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
      'name' => 'label',
      'email' => 'field_ci_email',
      'web page' => 'field_ci_webpage',
    ];
  }

}

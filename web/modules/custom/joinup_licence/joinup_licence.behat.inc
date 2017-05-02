<?php

/**
 * @file
 * Contains \LicenceSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Behat step definitions for testing licences.
 */
class LicenceSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use EntityReferenceTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;

  /**
   * Test licences.
   *
   * @var \Drupal\rdf_entity\Entity\Rdf[]
   */
  protected $licences = [];

  /**
   * Navigates to the canonical page display of a licence.
   *
   * @param string $licence
   *   The title of the licence.
   *
   * @When (I )go to (the homepage of )the :licence licence
   * @When (I )visit (the homepage of )the :licence licence
   */
  public function visitLicence($licence) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $licence */
    $licence = $this->getLicenceByName($licence);
    $this->visitPath($licence->url());
  }

  /**
   * Creates a number of licences with data provided in a table.
   *
   * @param \Behat\Gherkin\Node\TableNode $licence_table
   *   The licence data.
   *
   * @codingStandardsIgnoreStart
   *   Table format:
   *   uri                          | title       | description        | type |
   *   http://joinup.eu/licence/foo | Foo Licence | Licence details... |      |
   *   http://joinup.eu/licence/bar | Bar Licence | Licence details... |      |
   * @codingStandardsIgnoreEnd
   *
   * Fields title and description are mandatory.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )licences:
   */
  public function givenLicences(TableNode $licence_table) {
    $aliases = self::licenceFieldAliases();

    foreach ($licence_table->getColumnsHash() as $licence) {
      $values = [];
      // Replace the column aliases with the actual field names.
      foreach ($licence as $key => $value) {
        if (array_key_exists($key, $aliases)) {
          $values[$aliases[$key]] = $value;
        }
        else {
          throw new \Exception("Unknown column '$key' in licence table.");
        }
      };

      $values = $this->convertEntityReferencesValues('rdf_entity', 'licence', $values);

      $this->createLicence($values);
    }
  }

  /**
   * Creates a licence with data provided in a table.
   *
   * Table format:
   * | uri         | http://joinup.eu/licence/foobar |
   * | title       | Licence title                   |
   * | description | Some licence stuff...           |
   * | type        |                                 |
   *
   * Fields title and description are required.
   *
   * @param \Behat\Gherkin\Node\TableNode $licence_table
   *   The licence data.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Given (the following )licence:
   */
  public function givenLicence(TableNode $licence_table) {
    $aliases = self::licenceFieldAliases();

    $values = [];
    // Replace the column aliases with the actual field names.
    foreach ($licence_table->getRowsHash() as $key => $value) {
      if (array_key_exists($key, $aliases)) {
        $values[$aliases[$key]] = $value;
      }
      else {
        throw new \Exception("Unknown column '$key' in solution table.");
      }
    };

    $values = $this->convertEntityReferencesValues('rdf_entity', 'licence', $values);

    $this->createLicence($values);
  }

  /**
   * Creates a licence from the given field data.
   *
   * @param array $values
   *   An associative array of values, keyed by property name.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   A new licence entity.
   */
  protected function createLicence(array $values) {
    $values['rid'] = 'licence';
    $values['id'] = isset($values['id']) ? $values['id'] : $this->getRandomUri();
    $licence = Rdf::create($values);
    $licence->save();
    $this->licences[$licence->id()] = $licence;

    return $licence;
  }

  /**
   * Deletes a licence.
   *
   * @param string $licence
   *   The title of the licence.
   *
   * @When (I )delete the :licence licence
   */
  public function deleteLicence($licence) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $licence */
    $this->getLicenceByName($licence)->delete();
  }

  /**
   * Returns the licence with the given title.
   *
   * If multiple licences have the same title,
   * the first one will be returned.
   *
   * @param string $title
   *   The licence title.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The asset distribution.
   */
  protected function getLicenceByName($title) {
    return $this->getRdfEntityByLabel($title, 'licence');
  }

  /**
   * Checks the number of available licences.
   *
   * @param int $number
   *   The expected number of licences.
   *
   * @Then I should have :number licence(s)
   */
  public function assertLicenceCount($number) {
    $this->assertRdfEntityCount($number, 'licence');
  }

  /**
   * Remove any created licences.
   *
   * @AfterScenario
   */
  public function cleanLicences() {
    // Remove any asset distributions that were created.
    foreach ($this->licences as $licences) {
      $licences->delete();
    }
  }

  /**
   * Field alias mapping.
   *
   * @return array
   *   Mapping.
   */
  protected static function licenceFieldAliases() {
    // Mapping alias - field name.
    return [
      'uri' => 'id',
      'title' => 'label',
      'description' => 'field_licence_description',
      'type' => 'field_licence_type',
    ];
  }

}

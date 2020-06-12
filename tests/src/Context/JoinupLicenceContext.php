<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityReferenceTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\joinup\Traits\UtilityTrait;

/**
 * Behat step definitions for testing licences.
 */
class JoinupLicenceContext extends RawDrupalContext {

  use EntityReferenceTrait;
  use RdfEntityTrait;
  use SearchTrait;
  use UtilityTrait;

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
   * @When I go to (the homepage of )the :licence licence
   * @When I visit (the homepage of )the :licence licence
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitLicence($licence) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getLicenceByName($licence);
    $this->visitPath($entity->toUrl()->toString());
  }

  /**
   * Navigates to the canonical page display of a licence.
   *
   * @param string $licence
   *   The title of the licence.
   * @param string $format
   *   The RDF serialization format.
   *
   * @When I go to (the homepage of )the :licence licence in the :format serialisation.
   * @When I visit (the homepage of )the :licence licence in the :format serialisation.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitLicenceWithFormat($licence, $format) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $entity */
    $entity = $this->getLicenceByName($licence);
    $this->visitPath($entity->toUrl('canonical', ['query' => ['_format' => $format]])->toString());
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
      }

      $values = $this->convertValueAliases($values);
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
    }

    $values = $this->convertValueAliases($values);
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
    $licence = $this->createRdfEntity('licence', $values);
    $this->licences[$licence->id()] = $licence;

    return $licence;
  }

  /**
   * Deletes a licence.
   *
   * @param string $licence
   *   The title of the licence.
   *
   * @When I delete the :licence licence
   */
  public function deleteLicence($licence) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $licence */
    $licence = $this->getLicenceByName($licence);
    // Do not send email for entities created by the API.
    // @todo: Convert the dynamic property to a proper temporary storage.
    // @see: https://www.drupal.org/node/2896474
    $licence->skip_notification = TRUE;
    $licence->delete();
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
  public function assertLicenceCount(int $number): void {
    $this->assertRdfEntityCount($number, 'licence');
  }

  /**
   * Remove any created licences.
   *
   * @AfterScenario
   */
  public function cleanLicences() {
    if (empty($this->licences)) {
      return;
    }

    // Since we might be cleaning up many licences, temporarily disable the
    // feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    // Remove any licences that were created through the API during the test.
    foreach ($this->licences as $licence) {
      $licence->skip_notification = TRUE;
      $licence->delete();
    }
    $this->licences = [];
    $this->enableCommitOnUpdate();
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
      'deprecated' => 'field_licence_deprecated',
      'spdx licence' => 'field_licence_spdx_licence',
      'legal type' => 'field_licence_legal_type',
    ];
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
  protected function convertValueAliases(array $fields) {
    $mapped_values = [
      'field_licence_deprecated' => ['no' => 0, 'yes' => 1],
    ];

    foreach ($fields as $field => $value) {
      if (isset($mapped_values[$field])) {
        if (!isset($mapped_values[$field][$value])) {
          throw new \Exception("Value $value is not an acceptable value for field $field.");
        }

        $fields[$field] = $mapped_values[$field][$value];
      }
    }

    // Convert any entity reference field label value with the entity id.
    $fields = $this->convertEntityReferencesValues('rdf_entity', 'licence', $this->parseRdfEntityFields($fields));

    return $fields;
  }

}

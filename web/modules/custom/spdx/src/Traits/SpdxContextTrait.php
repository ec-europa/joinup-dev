<?php

declare(strict_types = 1);

namespace Drupal\spdx\Traits;

use Behat\Gherkin\Node\TableNode;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\sparql_entity_storage\SparqlEntityStorageInterface;

/**
 * Behat step definitions for the SPDX Licences module.
 *
 * This code is reused both by the (deprecated) SpdxSubContext and the
 * (recommended) SpdxContext.
 *
 * @see \SpdxSubContext
 * @see \Drupal\Tests\spdx\Behat\SpdxContext
 */
trait SpdxContextTrait {

  /**
   * Keeps track of licences created during the scenario, for later cleanup.
   *
   * @var \Drupal\rdf_entity\RdfInterface[]
   */
  protected $licences = [];

  /**
   * Creates a number of SPDX licence records.
   *
   * Table format:
   * @codingStandardsIgnoreStart
   * | title       | ID  | see also                                                                                         | text                                                                                               |
   * | MIT License | MIT | opensource.org - https://opensource.org/licenses/MIT, mit-license.org - https://mit-license.org/ | "MIT License\n\nCopyright ©2019\n\nPermission is hereby granted, free of charge, to any person..." |
   * @codingStandardsIgnoreEnd
   *
   * @param \Behat\Gherkin\Node\TableNode $data
   *   The data for the licences to create, in table format.
   *
   * @throws \Exception
   *   Thrown when a column has a title that is not recognized.
   *
   * @Given (the following )SPDX licences:
   */
  public function givenSpdxLicences(TableNode $data): void {
    $aliases = self::FIELD_ALIASES;
    foreach ($data->getColumnsHash() as $licence_data) {
      $values = (object) ['rid' => 'spdx_licence'];

      // Replace the human readable aliases with the field machine names.
      foreach ($licence_data as $key => $value) {
        if (!array_key_exists($key, $aliases)) {
          throw new \Exception("Unknown column '$key' in the data table.");
        }
        $values->{$aliases[$key]} = $value;
      };

      $this->createLicence($values);
    }
  }

  /**
   * Navigates to the canonical page of the SPDX licence.
   *
   * @param string $licence
   *   The SPDX licence label.
   *
   * @throws \Exception
   *   Thrown when the licence does not exist, or cannot be loaded.
   *
   * @Given I visit the :licence SPDX licence
   * @Given I go to the( homepage of the) :licence SPDX licence
   */
  public function visitSpdxLicence(string $licence): void {
    $result = \Drupal::entityQuery('rdf_entity')
      ->condition('label', $licence)
      ->condition('rid', 'spdx_licence')
      ->range(0, 1)
      ->execute();

    if (empty($result)) {
      $message = "The SPDX licence with label '$licence' was not found.";
      throw new \InvalidArgumentException($message);
    }
    $licence = Rdf::load(reset($result));
    $this->visitPath($licence->toUrl()->toString());
  }

  /**
   * Removes any created SPDX licences.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one or more licences cannot be deleted.
   *
   * @AfterScenario
   */
  public function cleanSpdxLicences() {
    $this->getRdfStorage()->delete($this->licences);
  }

  /**
   * Creates a licence from the given value object.
   *
   * @param \stdClass $values
   *   A value object as used internally by Behat Drupal Extension.
   *
   * @throws \Exception
   *   Thrown when a field name is invalid.
   */
  protected function createLicence(\stdClass $values): void {
    // Convert line breaks in the licence text.
    if (!empty($values->field_spdx_licence_text)) {
      $values->field_spdx_licence_text = nl2br(str_replace('\n', "\n", $values->field_spdx_licence_text));
    }

    $this->parseEntityFields('rdf_entity', $values);
    $entity_id = $this->getDriver()->createEntity('rdf_entity', $values)->id;

    $this->licences[] = $this->getRdfStorage()->load($entity_id);
  }

  /**
   * Returns the RDF Entity storage handler.
   *
   * @return \Drupal\sparql_entity_storage\SparqlEntityStorageInterface
   *   The storage handler.
   */
  protected function getRdfStorage(): SparqlEntityStorageInterface {
    return \Drupal::entityTypeManager()->getStorage('rdf_entity');
  }

}

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
   * Creates one or many owners with data provided in a table.
   *
   * Table format:
   * | type                  | name             | id                           |
   * | Company               | Mightily Oats    | http://example.com/some/path |
   * | Local authority       | Gotham City Hall |                              |
   * | Private Individual(s) | Jane Roe         |                              |
   *
   * @param TableNode $owners_table
   *   The owners table.
   *
   * @throws \Behat\Mink\Exception\ExpectationException;
   *   If a wrong type has been passed.
   *
   * @Given (the following )owner/owners:
   */
  public function givenOwner(TableNode $owners_table) {
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    foreach ($owners_table->getHash() as $row) {
      // If no type was specified, fallback to 'Private Individual(s)'.
      $row['type'] = isset($row['type']) ? $row['type'] : 'Private Individual(s)';

      $terms = $term_storage->loadByProperties(['vid' => 'owner_type', 'name' => $row['type']]);
      if (empty($terms)) {
        throw new ExpectationException("Owner type '{$row['type']}' doesn't exist.", $this->getDriver());
      }

      $owner = Rdf::create([
        'rid' => 'owner',
        'id' => !empty($row['id']) ? $row['id'] : $this->getRandomUri(),
        'field_owner_type' => key($terms),
        'field_owner_name' => !empty($row['name']) ? $row['name'] : $this->getRandom()->name(8, TRUE),
      ]);
      $owner->save();

      $this->owners[$owner->id()] = $owner;
    }
  }

  /**
   * Checks the number of available owners.
   *
   * @param int $number
   *   The expected number of owners.
   *
   * @throws \Exception
   *   Throws an exception when the expected number is not equal to the given.
   *
   * @Then I should have :number owner/owners
   */
  public function assertOwnerCount($number) {
    $this->assertRdfEntityCount($number, 'owner');
  }

  /**
   * Deletes an owner entity.
   *
   * @param string $name
   *   The name of the owner to delete.
   *
   * @When I delete the :owner owner
   */
  public function deleteOwner($name) {
    $this->getRdfEntityByLabel($name, 'owner')->delete();
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
   * Sets type(s) of the owner field.
   *
   * @param string $options
   *    A comma delimited list of options to be checked.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When no select has been found.
   *
   * @When (I )set the Owner type(s) to :option
   */
  public function setOwnerType($options) {
    // Explode into an array.
    $options = array_unique(array_map('trim', explode(',', $options)));

    $field = $this->getSession()->getPage()->find('named', ['fieldset', 'Owner']);

    /** @var \Behat\Mink\Element\NodeElement[] $labels */
    $labels = $field->findAll('xpath', '//label//label');
    foreach ($labels as $label) {
      $option = $label->getText();
      if (in_array($option, $options)) {
        $field->checkField($option);
      }
      else {
        $field->uncheckField($option);
      }
    }
  }

}

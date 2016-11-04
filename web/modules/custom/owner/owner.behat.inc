<?php

/**
 * @file
 * Contains \OwnerSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
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
   * Selects option in select field at a Owner inline entity widget.
   *
   * @param string $option
   *    The option to choose from the dropdown list.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When no select has been found.
   *
   * @When (I )select :option in the dropdown( list) of the "Owner"( field)
   */
  public function selectOption($option) {
    // Fixes a step argument (with \\" replaced back to ")
    // @see: Behat\MinkExtension\Context\MinkContext::fixStepArgument
    $option = str_replace('\\"', '"', $option);

    $field = $this->getSession()->getPage()->find('named', ['fieldset', 'Owner']);
    if (!$select = $field->find('named', ['select', 'field_ar_owner[form][inline_entity_form][field_owner_type]'])) {
      if (!$select = $field->find('named', ['select', 'field_ar_owner[form][inline_entity_form][entities][0][form][field_owner_type]'])) {
        throw new ElementNotFoundException($this->getSession()->getDriver(), "No select in Owner. Tried: 'field_ar_owner[form][inline_entity_form][field_owner_type]', 'field_ar_owner[form][inline_entity_form][entities][0][form][field_owner_type]'");
      }
    }

    /** @var \Behat\Mink\Element\NodeElement $select */
    $select->selectOption($option);
  }

}

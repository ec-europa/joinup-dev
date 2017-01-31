<?php

/**
 * @file
 * Contains \OwnerSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\RandomGeneratorTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;

/**
 * Behat step definitions for testing owners.
 */
class OwnerSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use MaterialDesignTrait;
  use RandomGeneratorTrait;
  use RdfEntityTrait;
  use UtilityTrait;

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
   * | type                        | name        | id                      |
   * | Company,Industry consortium | Acme Inc.   | http://example.com/path |
   * | Local authority             | Gotham City |                         |
   * | Private Individual(s)       | Jane Roe    |                         |
   *
   * @param TableNode $owners_table
   *   The owners table.
   *
   * @throws \Exception;
   *   If a wrong type has been passed.
   *
   * @Given (the following )owner/owners:
   */
  public function givenOwner(TableNode $owners_table) {
    foreach ($owners_table->getHash() as $row) {
      // If no type was specified, fallback to 'Private Individual(s)'.
      $types = isset($row['type']) ? array_map('trim', explode(',', $row['type'])) : ['Private Individual(s)'];

      $query = \Drupal::entityQuery('taxonomy_term');
      $tids = $query
        ->condition('vid', 'owner_type')
        ->condition('name', $types, 'IN')
        ->execute();

      $found = array_map(function (TermInterface $term) {
        return $term->label();
      }, Term::loadMultiple($tids));

      if ($not_found = array_diff($types, $found)) {
        throw new \Exception("Non-existent owner types: " . implode(', ', $not_found));
      }

      $owner = Rdf::create([
        'rid' => 'owner',
        'id' => !empty($row['id']) ? $row['id'] : $this->getRandomUri(),
        'field_owner_type' => $tids,
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
   * @throws \Exception
   *   Thrown when the owner field appears empty in the page.
   *
   * @When (I )set the Owner type(s) to :option
   */
  public function setOwnerType($options) {
    // Explode into an array.
    $options = $this->explodeCommaSeparatedStepArgument($options);

    /** @var \Behat\Mink\Element\NodeElement $owner_fieldset */
    $owner_fieldset = $this->getSession()->getPage()->find('named', ['fieldset', 'Type']);

    /** @var \Behat\Mink\Element\NodeElement[] $labels */
    $labels = $owner_fieldset->findAll('xpath', '//label//label');

    if (empty($labels)) {
      throw new \Exception('No options found in the owner field.');
    }

    foreach ($labels as $label) {
      $option = $label->getText();
      if (in_array($option, $options)) {
        $this->checkMaterialDesignField($option, $owner_fieldset);
      }
      else {
        $this->uncheckMaterialDesignField($option, $owner_fieldset);
      }
    }
  }

  /**
   * Navigates to the canonical page display of an owner.
   *
   * @param string $owner
   *   The label of the owner.
   *
   * @When (I )go to (the homepage of )the :owner owner
   * @When (I )visit (the homepage of )the :owner owner
   */
  public function visitOwner($owner) {
    /** @var \Drupal\rdf_entity\Entity\Rdf $owner */
    $owner = $this->getRdfEntityByLabel($owner);
    $this->visitPath($owner->url());
  }

}

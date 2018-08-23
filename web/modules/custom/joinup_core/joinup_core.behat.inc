<?php

/**
 * @file
 * Contains \JoinupSubContext.
 */

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\EntityTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for functionalities provided by Joinup core module.
 */
class JoinupCoreSubContext extends DrupalSubContextBase {

  use UtilityTrait;
  use TraversingTrait;

  use EntityTrait;

  /**
   * Asserts that only the expected chips are shown in the page.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table containing the expected chip labels.
   *
   * @Then the page should show( only) the chips:
   */
  public function assertChipElements(TableNode $table) {
    $chips = $this->getSession()->getPage()->findAll('css', '.mdl-chip__text');
    $found = array_map(function ($element) {
      /** @var \Behat\Mink\Element\NodeElement $element */
      return $element->getText();
    }, $chips);

    $expected = $table->getColumn(0);
    Assert::assertEquals($expected, $found, "The expected chip elements don't match the ones found in the page", 0.0, 10, TRUE);
  }

  /**
   * Clicks the remove button in a chip.
   *
   * @param string $text
   *   The text or partial text of the chip.
   *
   * @throws \Exception
   *   Thrown when either the chip or its remove button are not found.
   *
   * @Then I press the remove button on the chip :text
   */
  public function clickRemoveChipButton($text) {
    // Find the element that contains the user name.
    $xpath = "//span[contains(concat(' ', normalize-space(@class), ' '), ' mdl-chip__text ')][contains(text(), '$text')]";
    $chip = $this->getSession()->getPage()->find('xpath', $xpath);
    if (!$chip) {
      throw new Exception("The chip containing the label '$text' was not found on the page.");
    }

    // Find the related button.
    $xpath = "/following-sibling::button[@type='submit'][contains(concat(' ', normalize-space(@class), ' '), ' mdl-chip__action ')]";
    $button = $chip->find('xpath', $xpath);
    if (!$button) {
      throw new \Exception("Couldn't find the button to remove the chip '$text'.");
    }

    $button->press();
  }

  /**
   * Asserts that a list of fields are disabled.
   *
   * @param string $fields
   *   A list of comma separated field labels.
   *
   * @throws \Exception
   *   Thrown when a field is not found by name or is not disabled.
   *
   * @Then the following fields should be disabled :fields
   */
  public function givenDisabledFields(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $found = [];
    foreach ($fields as $field) {
      $element = $this->findDisabledField($field);
      if (empty($element)) {
        $found[] = $field;
      }
    }

    if (!empty($found)) {
      throw new Exception('The following fields were not found or were enabled: ' . implode(', ', $found));
    }
  }

  /**
   * Asserts that a list of fields are not disabled.
   *
   * @param string $fields
   *   A list of comma separated field labels.
   *
   * @throws \Exception
   *   Thrown when a field is not found by name or is disabled.
   *
   * @Then the following fields should not be disabled :fields
   */
  public function givenNotDisabledFields(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $found = [];
    foreach ($fields as $field) {
      $element = $this->findDisabledField($field);
      if (!empty($element)) {
        $found[] = $field;
      }
    }

    if (!empty($found)) {
      throw new Exception('The following fields were disabled: ' . implode(', ', $found));
    }
  }

  /**
   * Visits a taxonomy term page.
   *
   * @param string $name
   *   The taxonomy term name.
   *
   * @Given I go to the :name (taxonomy )term( page)
   */
  public function visitTaxonomyTermPage(string $name) {
    $term = static::getEntityByLabel('taxonomy_term', $name);
    $this->visitPath($term->toUrl()->toString());
  }

}

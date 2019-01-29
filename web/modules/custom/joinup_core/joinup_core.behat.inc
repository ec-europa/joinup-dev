<?php

/**
 * @file
 * Contains \JoinupCoreSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\NodeTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\og\OgGroupAudienceHelperInterface;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for functionalities provided by Joinup core module.
 */
class JoinupCoreSubContext extends DrupalSubContextBase {

  use UtilityTrait;
  use TraversingTrait;

  use EntityTrait;
  use NodeTrait;
  use UserTrait;
  use RdfEntityTrait;

  /**
   * Asserts that only the expected chips are shown in the page.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table containing the expected chip labels.
   *
   * @Then the page should show( only) the chips:
   */
  public function assertChipElements(TableNode $table): void {
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
  public function clickRemoveChipButton(string $text): void {
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
   * Visits a taxonomy term page.
   *
   * @param string $name
   *   The taxonomy term name.
   *
   * @Given I go to the :name (taxonomy )term( page)
   */
  public function visitTaxonomyTermPage(string $name): void {
    $term = static::getEntityByLabel('taxonomy_term', $name);
    $this->visitPath($term->toUrl()->toString());
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
   * Asserts the content type of the response from the server.
   *
   * @param string $content_type
   *   The expected content type.
   *
   * @Then the content type of the response should be :content_type
   */
  public function assertResponseContentType(string $content_type): void {
    $this->assertSession()->responseHeaderEquals('Content-Type', $content_type);
  }

  /**
   * Checks if the given node belongs to the given group.
   *
   * If there are multiple nodes or groups with the same name, then only
   * the first one is checked.
   *
   * @param string $group_label
   *   The name of the collection or solution to check.
   * @param string $group_type
   *   The type of the group.
   * @param string $content_title
   *   The title of the node to check.
   *
   * @throws \Exception
   *   Thrown when a node with the given title doesn't exist.
   *
   * @Then the :group_label :group_type should have a custom page titled :group_title
   * @Then the :group_label :group_type should have a community content titled :group_title
   */
  public function assertNodeOgMembership(string $group_label, string $group_type, string $content_title): void {
    $group = $this->getRdfEntityByLabel($group_label, $group_type);
    $node = $this->getNodeByTitle($content_title);
    if ($node->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->target_id !== $group->id()) {
      throw new \Exception("The node '$content_title' is not associated with collection '{$group->label()}'.");
    }
  }

  /**
   * Asserts the order of elements.
   *
   * @Then I should see the following group menu items in the specified order:
   */
  public function assertRepeatedElementContainsText(TableNode $table): void {
    $parent = $this->getSession()->getPage()->findAll('css', '.block-group-menu-blocknavigation li.sidebar-menu__item');
    $i = 0;
    foreach ($table->getHash() as $repeatedElement) {
      $child = $parent[$i];
      $actual_text = $child->find('css', 'a.sidebar-menu__link')->getText();
      Assert::assertEquals($repeatedElement['text'], $actual_text);
      $i++;
    }
  }

  /**
   * Searches for links matching the criteria and clicks on the last of them.
   *
   * Since the results are sequential, the last link in the results is also the
   * last instance of the link in the page matching the given criteria.
   *
   * @param string $link
   *   The link locator.
   *
   * @Given I click the last :link link
   */
  public function assertClickLastLink(string $link): void {
    $locator = ['link', $link];
    $links = $this->getSession()->getPage()->findAll('named', $locator);
    $link = end($links);
    $link->click();
  }

}

<?php

/**
 * @file
 * Contains step definitions for the Joinup project.
 */

declare(strict_types = 1);

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Site\Settings;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\TagTrait;
use Drupal\joinup\HtmlManipulator;
use Drupal\joinup\Traits\AntibotTrait;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\ContextualLinksTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\PageCacheTrait;
use Drupal\joinup\Traits\SearchTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UserTrait;
use Drupal\joinup\Traits\UtilityTrait;
use Drupal\joinup_core\JoinupVersionInterface;
use Joinup\TaskRunner\Traits\TaskRunnerTrait;
use LoversOfBehat\TableExtension\Hook\Scope\AfterTableFetchScope;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\ExpectationFailedException;
use WebDriver\Exception;
use WebDriver\Key;

/**
 * Defines generic step definitions.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  use AntibotTrait;
  use BrowserCapabilityDetectionTrait;
  use ContextualLinksTrait;
  use EntityTrait;
  use MaterialDesignTrait;
  use PageCacheTrait;
  use SearchTrait;
  use TagTrait;
  use TaskRunnerTrait;
  use TraversingTrait;
  use UserTrait;
  use UtilityTrait;

  /**
   * The Joinup version, retrieved from the `VERSION` file in the project root.
   *
   * Will contain the contents of the file, or FALSE if the file is not present.
   *
   * @var string|bool
   */
  protected $version;

  /**
   * The latest file ID.
   *
   * @var int
   */
  protected static $lastFileId;

  /**
   * Checks that a 200 OK response occurred.
   *
   * @Then I should get a valid web page
   */
  public function assertSuccessfulResponse(): void {
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Checks that a 403 Access Denied error occurred.
   *
   * @Then I should get an access denied error
   */
  public function assertAccessDenied(): void {
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Assert that certain fields are present on the page.
   *
   * @param string $fields
   *   Fields.
   *
   * @throws \Exception
   *   Thrown when an expected field is not present.
   *
   * @Then (the following )field(s) should be present :fields
   */
  public function assertFieldsPresent(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    $not_found = [];
    foreach ($fields as $field) {
      $is_found = (bool) $this->findAnyFormField($field, $page);
      if (!$is_found) {
        $not_found[] = $field;
      }
    }
    if ($not_found) {
      throw new \Exception("Field(s) expected, but not found: " . implode(', ', $not_found));
    }
  }

  /**
   * Assert that certain fields are not present on the page.
   *
   * @param string $fields
   *   Fields.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Then (the following )field(s) should not be present :fields
   */
  public function assertFieldsNotPresent(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    foreach ($fields as $field) {
      $is_found = $page->findField($field);
      if ($is_found) {
        throw new \Exception("Field should not be found, but is present: " . $field);
      }
    }
  }

  /**
   * Assert that certain fields are present and visible on the page.
   *
   * @param string $fields
   *   Fields.
   *
   * @throws \Exception
   *   Thrown when an expected field is not present or is not visible.
   *
   * @Then (the following )field(s) should be visible :fields
   */
  public function assertFieldsVisible(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    $not_found = [];
    $not_visible = [];
    foreach ($fields as $field) {
      $element = $this->findAnyFormField($field, $page);
      if (!$element) {
        $not_found[] = $field;
        continue;
      }
      elseif (!$element->isVisible()) {
        // Retrieve the first standard form item wrapper around our field.
        // Some fields, like text areas or checkboxes, are actually hidden but
        // their label and container are not.
        $wrapper = $element->find('xpath', "ancestor-or-self::div[@class and contains(concat(' ', normalize-space(@class), ' '), ' form-item ')][1]");

        if ($wrapper && !$wrapper->isVisible()) {
          $not_visible[] = $field;
        }
      }
    }

    if ($not_found) {
      throw new \Exception("Field(s) expected, but not found: " . implode(', ', $not_found));
    }
    if ($not_visible) {
      throw new \Exception("Field(s) expected, but not visible: " . implode(', ', $not_visible));
    }
  }

  /**
   * Assert that certain fields are present but not visible on the page.
   *
   * @param string $fields
   *   Fields.
   *
   * @throws \Exception
   *   Thrown when a field is not present or is visible.
   *
   * @Then (the following )field(s) should not be visible :fields
   */
  public function assertFieldsNotVisible(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    $not_found = [];
    $visible = [];
    foreach ($fields as $field) {
      $element = $this->findAnyFormField($field, $page);
      if (!$element) {
        $not_found[] = $field;
        continue;
      }

      // Retrieve the first standard form item wrapper around our field.
      // Some fields, like text areas or checkboxes, are actually hidden but
      // their label and container are not.
      $wrapper = $element->find('xpath', "ancestor-or-self::div[@class and contains(concat(' ', normalize-space(@class), ' '), ' form-item ')][1]");
      // Neither the field or its wrapper should be visible at all.
      if ($element->isVisible() || !empty($wrapper) && $wrapper->isVisible()) {
        $visible[] = $field;
      }
    }

    if ($not_found) {
      throw new \Exception("Field(s) expected, but not found: " . implode(', ', $not_found));
    }
    if ($visible) {
      throw new \Exception("Field(s) should not be visible: " . implode(', ', $visible));
    }
  }

  /**
   * Assert that certain fieldsets are present on the page.
   *
   * @param string $fieldsets
   *   The fieldset names to search for, separated by comma.
   *
   * @throws \Exception
   *   Thrown when a fieldset is not found.
   *
   * @Then (the following )field widgets should be present :fieldsets
   * @Then (the following )fieldsets should be present :fieldsets
   */
  public function assertFieldsetsPresent(string $fieldsets): void {
    $fieldsets = $this->explodeCommaSeparatedStepArgument($fieldsets);
    $page = $this->getSession()->getPage();
    $not_found = [];
    foreach ($fieldsets as $fieldset) {
      $is_found = $page->find('named', ['fieldset', $fieldset]);
      if (!$is_found) {
        $not_found[] = $fieldset;
      }
    }
    if ($not_found) {
      throw new \Exception("Fieldset(s) expected, but not found: " . implode(', ', $not_found));
    }
  }

  /**
   * Assert that certain fieldsets are present and visible on the page.
   *
   * @param string $fieldsets
   *   The fieldset names to search for, separated by comma.
   *
   * @throws \Exception
   *   Thrown when a fieldset is not found or is not visible.
   *
   * @Then (the following )field widgets should be visible :fieldsets
   * @Then (the following )fieldsets should be visible :fieldsets
   */
  public function assertFieldsetsVisible(string $fieldsets): void {
    $fieldsets = $this->explodeCommaSeparatedStepArgument($fieldsets);
    $page = $this->getSession()->getPage();
    $not_found = [];
    $not_visible = [];
    foreach ($fieldsets as $fieldset) {
      $is_found = $page->find('named', ['fieldset', $fieldset]);
      if (!$is_found) {
        $not_found[] = $fieldset;
      }

      if (!$is_found->isVisible()) {
        $not_visible[] = $fieldset;
      }
    }

    if ($not_found) {
      throw new \Exception("Fieldset(s) expected, but not found: " . implode(', ', $not_found));
    }
    if ($not_visible) {
      throw new \Exception("Fieldset(s) expected, but not visible: " . implode(', ', $not_visible));
    }
  }

  /**
   * Assert that certain fieldsets are present and visible on the page.
   *
   * @param string $fieldsets
   *   The fieldset names to search for, separated by comma.
   *
   * @throws \Exception
   *   Thrown when a fieldset is not found or is visible.
   *
   * @Then (the following )field widgets should not be visible :fieldsets
   * @Then (the following )fieldsets should not be visible :fieldsets
   */
  public function assertFieldsetsNotVisible(string $fieldsets): void {
    $fieldsets = $this->explodeCommaSeparatedStepArgument($fieldsets);
    $page = $this->getSession()->getPage();
    $not_found = [];
    $visible = [];
    foreach ($fieldsets as $fieldset) {
      $is_found = $page->find('named', ['fieldset', $fieldset]);
      if (!$is_found) {
        $not_found[] = $fieldset;
      }

      if ($is_found->isVisible()) {
        $visible[] = $fieldset;
      }
    }

    if ($not_found) {
      throw new \Exception("Fieldset(s) expected, but not found: " . implode(', ', $not_found));
    }
    if ($visible) {
      throw new \Exception("Fieldset(s) should not be visible: " . implode(', ', $visible));
    }
  }

  /**
   * Checks that a given image is present in the page.
   *
   * @param string $filename
   *   The file name.
   *
   * @Then I (should )see the image :filename
   */
  public function assertImagePresent(string $filename): void {
    Assert::assertTrue($this->findImageInRegion($filename));
  }

  /**
   * Checks that a given image is not present in the page.
   *
   * @param string $filename
   *   The filename.
   *
   * @Then I should not see the image :filename
   */
  public function assertImageNotPresent(string $filename): void {
    Assert::assertFalse($this->findImageInRegion($filename));
  }

  /**
   * Checks that a given image is present in a given tile.
   *
   * @param string $filename
   *   The filename.
   * @param string $tile
   *   The tile title.
   *
   * @Then I (should )see the image ":filename" in the :tile tile
   */
  public function assertImagePresentInRegion(string $filename, string $tile): void {
    $tile = $this->getTileByHeading($tile);
    Assert::assertTrue($this->findImageInRegion($filename, $tile));
  }

  /**
   * Checks that a given image is not present in a given tile.
   *
   * @param string $filename
   *   The filename.
   * @param string $tile
   *   The tile title.
   *
   * @Then I should not see the image :filename in the :tile tile
   */
  public function assertImageNotPresentInRegion(string $filename, string $tile): void {
    $tile = $this->getTileByHeading($tile);
    Assert::assertFalse($this->findImageInRegion($filename, $tile));
  }

  /**
   * Maximize the browser window for javascript tests so elements are visible.
   *
   * @Given I maximize the browser window
   */
  public function maximizeBrowserWindow(): void {
    $this->getSession()->getDriver()->maximizeWindow();
  }

  /**
   * Checks that the option with the given text is selected.
   *
   * @param string $text
   *   Text value of the option to check.
   *
   * @throws \Exception
   *   Thrown when an option with the given text does not exist, or when it is
   *   not selected.
   *
   * @Then the option :option should be selected
   */
  public function assertOptionSelected(string $text): void {
    $option = $this->getSession()->getPage()->find('xpath', '//option[text()="' . $text . '"]');

    if (!$option) {
      throw new \Exception("Option with text $text not found in the page.");
    }

    if (!$option->isSelected()) {
      throw new \Exception("The option with text $text is not selected.");
    }
  }

  /**
   * Checks that the option with the given text is not selected.
   *
   * @param string $text
   *   Text value of the option to check.
   *
   * @throws \Exception
   *   Thrown when an option with the given text does not exist, or when it is
   *   selected.
   *
   * @Then the option :option should not be selected
   */
  public function assertOptionNotSelected(string $text): void {
    $option = $this->getSession()->getPage()->find('xpath', '//option[text()="' . $text . '"]');

    if (!$option) {
      throw new \Exception("Option with text $text not found in the page.");
    }

    if ($option->isSelected()) {
      throw new \Exception("The option with text $text is selected.");
    }
  }

  /**
   * Find the selected option of the select and check the text.
   *
   * @param string $option
   *   Text value of the option to find.
   * @param string $select
   *   CSS selector of the select field.
   *
   * @throws \Exception
   *   Thrown when the select is not found in the page or the selected option is
   *   not the expected one.
   *
   * @Then the option with text :option from select :select is selected
   */
  public function assertFieldOptionSelected(string $option, string $select): void {
    $this->assertFieldOptionSelectedInRegion($option, $select);
  }

  /**
   * Checks if an option is selected in a specific select element in a region.
   *
   * @param string $option
   *   Text value of the option to find.
   * @param string $select
   *   CSS selector of the select field.
   * @param \Behat\Mink\Element\TraversableElement $region
   *   (optional) The region to search in. Defaults to the whole page.
   *
   * @throws \Exception
   *   Thrown when the select is not found in the page or the selected option is
   *   not the expected one.
   */
  protected function assertFieldOptionSelectedInRegion(string $option, string $select, TraversableElement $region = NULL): void {
    if (empty($region)) {
      $region = $this->getSession()->getPage();
    }

    $element = $this->findSelect($select, $region);
    if (!$element) {
      throw new \Exception(sprintf('The select "%s" was not found.', $select));
    }

    $option_element = $element->find('xpath', '//option[@selected="selected"]');
    if (!$option_element) {
      throw new \Exception(sprintf('No option is selected in the %s select', $select));
    }

    if ($option_element->getText() !== $option) {
      throw new \Exception(sprintf('The option "%s" was expected to be selected, but %s was selected instead.', $option, $this->getSession()->getCurrentUrl(), $option_element->getHtml()));
    }
  }

  /**
   * Checks that a certain radio input is selected in a specific field.
   *
   * @param string $radio
   *   The label of the radio input to find.
   * @param string $field
   *   The label of the field the radio is part of.
   *
   * @throws \Exception
   *   Thrown when the field or the radio is not found, or if the radio is not
   *   selected.
   *
   * @Then the radio button :radio from field :field should be selected
   */
  public function assertFieldRadioSelected(string $radio, string $field): void {
    // Find the grouping fieldset that contains the radios field.
    $fieldset = $this->getSession()->getPage()->find('named', ['fieldset', $field]);

    if (!$field) {
      throw new \Exception("The field '$field' was not found in the page.");
    }

    // Find the field inside the container itself. Use the findField() instead
    // of custom xpath because we are trying to find the radio by label.
    $input = $fieldset->findField($radio);

    // Verify that we have found a valid '//input[@type="radio"]'.
    if (!$input || $input->getTagName() !== 'input' || $input->getAttribute('type') !== 'radio') {
      throw new \Exception("The radio '$radio' was not found in the page.");
    }

    if (!$input->isChecked()) {
      throw new \Exception("The radio '$radio' is not selected.");
    }
  }

  /**
   * Asserts that the radio button option should be selected.
   *
   * @param string $radio
   *   The radio button title.
   *
   * @Then the :radio radio button should not be selected
   */
  public function assertRadioButtonNotChecked(string $radio): void {
    $session = $this->getSession();
    $page = $session->getPage();
    $radio = $page->find('named', ['radio', $radio]);
    if ($radio->isChecked()) {
      throw new ExpectationException($session->getDriver(), 'The radio button is checked but it should not be.');
    }
  }

  /**
   * Checks that a select element does not have the given text option selected.
   *
   * @param string $option
   *   Text value of the option to find.
   * @param string $select
   *   CSS selector of the select field.
   *
   * @throws \Exception
   *
   * @Then the option with text :option from select :select is not selected
   */
  public function assertFieldOptionNotSelected(string $option, string $select): void {
    $selectField = $this->getSession()->getPage()->find('css', $select);
    if ($selectField === NULL) {
      throw new \Exception(sprintf('The select "%s" was not found in the page %s', $select, $this->getSession()->getCurrentUrl()));
    }

    $optionField = $selectField->find('xpath', '//option[@selected="selected"]');
    if ($optionField !== NULL) {
      if ($optionField->getHtml() == $option) {
        throw new \Exception(sprintf('The option "%s" was selected in the page %s', $option, $this->getSession()->getCurrentUrl()));
      }
    }
  }

  /**
   * Checks if a node of a certain type with a given title exists.
   *
   * @param string $type
   *   The node type.
   * @param string $title
   *   The title of the node.
   *
   * @Then I should have a :type (content )page titled :title
   */
  public function assertContentPageByTitle(string $type, string $title): void {
    $type = $this->getEntityByLabel('node_type', $type);
    // If the node doesn't exist, the exception will be thrown here.
    $this->getEntityByLabel('node', $title, $type->id());
  }

  /**
   * Checks the users existence.
   *
   * @param string $username
   *   The username of the user.
   *
   * @throws \Exception
   *   Thrown when the user is not found.
   *
   * @Then I should have a :username user
   */
  public function assertUserExists(string $username): void {
    $user = $this->getUserByName($username);

    if (empty($user)) {
      throw new \Exception("Unable to load expected user " . $username);
    }
  }

  /**
   * Checks that user doesn't exist.
   *
   * @param string $username
   *   The username of the user.
   *
   * @throws \Exception
   *   Thrown when the user is found.
   *
   * @Then I should not have a :username user
   */
  public function assertUserNotExists(string $username): void {
    if (user_load_by_name($username)) {
      throw new \Exception("The user '$username' exists but it should not exist.");
    }
  }

  /**
   * Deletes the user account with the given name.
   *
   * This intended to be used for user accounts that are created through the UI
   * and are not cleaned up automatically when a test ends.
   *
   * @param string $username
   *   The name of the user to delete.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when an error occurs while the user is being deleted.
   *
   * @Then I delete the :username user
   */
  public function deleteUser(string $username): void {
    /** @var \Drupal\user\UserInterface $user */
    $user = $this->getUserByName($username);
    $user->delete();
  }

  /**
   * Click on an element by css class.
   *
   * @param string $element
   *   The element label to click.
   *
   * @Then /^I click on element "([^"]*)"$/
   */
  public function iClickOn(string $element): void {
    $page = $this->getSession()->getPage();
    $findName = $page->find('css', $element);
    if (!$findName) {
      throw new \Exception($element . " could not be found");
    }
    $findName->click();
  }

  /**
   * Clicks a contextual link directly, without the need for javascript.
   *
   * @param string $text
   *   The text of the link.
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   When either the region or the link are not found.
   *
   * @Then I click the contextual link :text in the :region region
   */
  public function iClickTheContextualLinkInTheRegion(string $text, string $region): void {
    $this->clickContextualLink($this->getRegion($region), $text);
  }

  /**
   * Asserts that a certain contextual link is present in a region.
   *
   * @param string $text
   *   The text of the link.
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   Thrown when the contextual link is not found in the region.
   *
   * @Then I (should )see the contextual link :text in the :region region
   */
  public function assertContextualLinkInRegionPresent(string $text, string $region): void {
    $links = $this->findContextualLinkPaths($this->getRegion($region));

    if (!isset($links[$text])) {
      throw new \Exception(sprintf('Contextual link %s expected but not found in the region %s', $text, $region));
    }
  }

  /**
   * Asserts that a certain contextual link is present in the page.
   *
   * @param string $text
   *   The text of the link.
   *
   * @throws \Exception
   *   Thrown when the contextual link is not found in the page.
   *
   * @Then I (should )see the contextual link :text
   */
  public function assertContextualLinkInPagePresent(string $text): void {
    $region = $this->getSession()->getPage();
    $links = $this->findContextualLinkPaths($region);

    if (!isset($links[$text])) {
      throw new \Exception(sprintf('Contextual link %s expected but not found in the region %s', $text, $region));
    }
  }

  /**
   * Asserts that a certain contextual link is not present in a region.
   *
   * @param string $text
   *   The text of the link.
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   Thrown when the contextual link is found in the region.
   *
   * @Then I should not see the contextual link :text in the :region region
   */
  public function assertContextualLinkInRegionNotPresent(string $text, string $region): void {
    $links = $this->findContextualLinkPaths($this->getRegion($region));

    if (isset($links[$text])) {
      throw new \Exception(sprintf('Unexpected contextual link %s found in the region %s', $text, $region));
    }
  }

  /**
   * Asserts that a certain contextual link is not present in the page.
   *
   * @param string $text
   *   The text of the link.
   *
   * @throws \Exception
   *   Thrown when the contextual link is found in the page.
   *
   * @Then I should not see the contextual link :text
   */
  public function assertContextualLinkInPageNotPresent(string $text): void {
    $region = $this->getSession()->getPage();
    $links = $this->findContextualLinkPaths($region);

    if (isset($links[$text])) {
      throw new \Exception(sprintf('Unexpected contextual link %s found in the region %s', $text, $region));
    }
  }

  /**
   * Asserts that no contextual links are present in a region.
   *
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   Thrown when any contextual link is found in the region.
   *
   * @Then I should not see any contextual links in the :region region
   */
  public function assertNoContextualLinksInRegion(string $region): void {
    $links = $this->findContextualLinkPaths($this->getRegion($region));

    if (!empty($links)) {
      throw new \Exception(sprintf('Unexpected contextual links found in the region %s', $region));
    }
  }

  /**
   * Checks that the contextual links button is visible in the browser.
   *
   * This checks actual visibility in the browser, so this needs the
   * '@javascript' tag to be present on the test scenario.
   *
   * @param string $region
   *   The region in which the contextual link is expected to be visible.
   *
   * @Then I (should )see the contextual links button in the :region( region)
   * @Then the contextual links button should be visible in the :region( region)
   */
  public function assertContextualLinkButtonVisible(string $region): void {
    $button = $this->findContextualLinkButton($this->getRegion($region));
    $this->assertVisuallyVisible($button);
  }

  /**
   * Clicks the contextual links button in the given region.
   *
   * @param string $region
   *   The name of the region where the contextual links button resides.
   *
   * @When I click the contextual links button in the :region( region)
   */
  public function clickContextualLinkButton(string $region): void {
    $button = $this->findContextualLinkButton($this->getRegion($region));
    $button->click();
  }

  /**
   * Checks that the given named element is not visible for human eyes.
   *
   * This is similar to methods like MinkContext::assertLinkRegion() but
   * instead of verifying the presence of the element in the DOM it checks
   * with the browser if the element is actually invisible.
   *
   * This is intended for verifying things like hover states.
   *
   * @param string $locator
   *   The xpath locator.
   * @param string $element
   *   The element tag.
   * @param string $region
   *   The region selector.
   *
   * @Then the :locator :element in the :region( region) should not be visible
   */
  public function assertElementNotVisibleInRegion(string $locator, string $element, string $region): void {
    $region = $this->getRegion($region);
    $element = $this->findNamedElementInRegion($locator, $element, $region);
    $this->assertNotVisuallyVisible($element);
  }

  /**
   * Checks that the given named element is visible for human eyes.
   *
   * This is similar to methods like MinkContext::assertLinkRegion() but instead
   * of verifying the presence of the element in the DOM it checks with the
   * browser if the element is actually visible.
   *
   * This is intended for verifying things like hover states.
   *
   * @param string $locator
   *   The xpath locator.
   * @param string $element
   *   The element tag.
   * @param string $region
   *   The region selector.
   *
   * @Then the :locator :element in the :region( region) should be visible
   */
  public function assertElementVisibleInRegion(string $locator, string $element, string $region): void {
    $region = $this->getRegion($region);
    $element = $this->findNamedElementInRegion($locator, $element, $region);
    $this->assertVisuallyVisible($element);
  }

  /**
   * Finds a vertical tab given its title and clicks it.
   *
   * @param string $tab
   *   The tab title.
   *
   * @throws \Exception
   *   When the tab is not found on the page.
   *
   * @When I click( the) :tab tab
   */
  public function clickVerticalTabLink(string $tab): void {
    // When this is running in a browser without JavaScript the vertical tabs
    // are rendered as a details element.
    if (!$this->browserSupportsJavaScript()) {
      return;
    }

    $this->findVerticalTab($tab)->clickLink($tab);
  }

  /**
   * Asserts that a vertical tab is active.
   *
   * @param string $tab
   *   The tab title.
   *
   * @throws \Exception
   *   When the tab is not found on the page or it's not active.
   *
   * @Then the :tab tab should be active
   */
  public function assertVerticalTabActive(string $tab): void {
    $element = $this->findVerticalTab($tab);

    if (!$element->hasClass('is-selected')) {
      throw new \Exception("The tab '$tab' is not active.");
    }
  }

  /**
   * Creates testing terms for scenarios tagged with @terms tag.
   *
   * Limitation: It creates terms with maximum 2 level hierarchy.
   *
   * @beforeScenario @terms
   */
  public function provideTestingTerms(): void {
    $fixture = file_get_contents(__DIR__ . '/../../fixtures/testing_terms.yml');
    $hierarchy = Yaml::decode($fixture);
    foreach ($hierarchy as $vid => $terms) {
      foreach ($terms as $key => $data) {
        $has_children = is_array($terms);
        $name = $has_children ? $key : $data;
        $term = (object) [
          'vocabulary_machine_name' => $vid,
          'name' => $name,
        ];
        $this->termCreate($term);
        $parent_tid = $term->tid;
        if ($has_children) {
          foreach ($data as $name) {
            $term = (object) [
              'vocabulary_machine_name' => $vid,
              'name' => $name,
              'parent' => $parent_tid,
            ];
            $this->termCreate($term);
          }
        }
      }
    }
  }

  /**
   * Fills a multi-value field.
   *
   * In Drupal a field can have a cardinality bigger than one. In that case,
   * the field widget will be rendered multiple times. This method will fill
   * each item with the corresponding value.
   * This doesn't handle widgets with multiple inputs and it relies on the
   * number of items to match the number of values. Clicking the
   * button to add more items is left to the user.
   *
   * @param string $field
   *   The name of the field.
   * @param string $values
   *   A comma separated list of values.
   *
   * @throws \Exception
   *   When the field cannot be found or the number of values is different from
   *   the number of elements found.
   *
   * @When I fill in :field with values :values
   */
  public function fillFieldWithValues(string $field, string $values): void {
    $values = $this->explodeCommaSeparatedStepArgument($values);

    /** @var \Behat\Mink\Element\NodeElement[] $items */
    $items = $this->getSession()->getPage()->findAll('named', ['field', $field]);

    if (empty($items)) {
      throw new \Exception("Cannot find field $field.");
    }

    if (count($items) !== count($values)) {
      throw new \Exception('Expected ' . count($values) . ' items for field ' . $field . ', found ' . count($items));
    }

    foreach ($items as $delta => $item) {
      $item->setValue($values[$delta]);
    }
  }

  /**
   * Asserts that a whole region is not present in the page.
   *
   * @param string $region
   *   The name of the region.
   *
   * @throws \Exception
   *   Thrown when the region is found in the page.
   *
   * @Then I should not see the :region region
   */
  public function assertRegionNotPresent(string $region): void {
    $session = $this->getSession();
    $element = $session->getPage()->find('region', $region);
    if ($element) {
      throw new \Exception(sprintf('Region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }
  }

  /**
   * Asserts that the page title tag equals to some text.
   *
   * The assertion strips off the possible suffix "| Joinup".
   *
   * @param string $text
   *   The text to search for.
   *
   * @throws \Exception
   *   Thrown when the title tag is not found or the text doesn't match.
   *
   * @Then the HTML title of the page should be :text
   */
  public function assertPageTitleTagContainsText(string $text): void {
    $session = $this->getSession();
    $page_title = $session->getPage()->find('xpath', '//head/title');
    if (!$page_title) {
      throw new \Exception(sprintf('Page title tag not found on the page "%s".', $session->getCurrentUrl()));
    }

    $page_title = $page_title->getText();
    if (!$page_title) {
      throw new \Exception(sprintf('Page title tag is found but contains no text on page "%s".', $session->getCurrentUrl()));
    }

    if (strpos($page_title, ' | ') !== FALSE) {
      $page_title = implode(' | ', explode(' | ', $page_title, -1));
    }

    $page_title = trim($page_title);
    if ($page_title !== $text) {
      throw new \Exception(sprintf('Expected page title is "%s", but "%s" found.', $text, $page_title));
    }
  }

  /**
   * Asserts that the page contains a certain capitalised heading.
   *
   * @param string $heading
   *   The heading to search for.
   *
   * @Then I (should )see the capitalised heading :heading
   */
  public function assertCapitalisedHeading(string $heading): void {
    $heading = strtoupper($heading);
    $element = $this->getSession()->getPage();
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
      $results = $element->findAll('css', $tag);
      foreach ($results as $result) {
        if ($result->getText() == $heading) {
          return;
        }
      }
    }
    throw new \Exception(sprintf("The text '%s' was not found in any heading on the page %s", $heading, $this->getSession()->getCurrentUrl()));
  }

  /**
   * Checks multiple headings on the page.
   *
   * Provide data in the following format:
   * | Heading 1 |
   * | Heading 2 |
   * | ...       |
   *
   * @param \Behat\Gherkin\Node\TableNode $headingsTable
   *   A list of headings.
   *
   * @Then I (should )see the following headings:
   */
  public function assertHeadings(TableNode $headingsTable): void {
    $page = $this->getSession()->getPage();
    $headings = $headingsTable->getColumn(0);
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
      $results = $page->findAll('css', $tag);
      foreach ($results as $result) {
        $key = array_search($result->getText(), $headings);
        if ($key === FALSE) {
          continue;
        }
        unset($headings[$key]);
      }
    }
    if (!empty($headings)) {
      throw new \Exception(sprintf("The following headings were not found on the page %s: '%s'", $this->getSession()->getCurrentUrl(), implode(', ', $headings)));
    }
  }

  /**
   * Checks if multiple headings are not present on the page.
   *
   * Provide data in the following format:
   * | Heading 1 |
   * | Heading 2 |
   * | ...       |
   *
   * @param \Behat\Gherkin\Node\TableNode $headingsTable
   *   A list of headings.
   *
   * @Then I should not see the following headings:
   */
  public function assertNoHeadings(TableNode $headingsTable): void {
    $page = $this->getSession()->getPage();
    $headings = $headingsTable->getColumn(0);
    $found_headings = [];
    foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tag) {
      $results = $page->findAll('css', $tag);
      foreach ($results as $result) {
        $key = array_search($result->getText(), $headings);
        if ($key !== FALSE) {
          $found_headings[] = $headings[$key];
        }
      }
    }
    if (!empty($found_headings)) {
      throw new \Exception(sprintf("The following headings were found on the page %s, but they shouldn't have been: '%s'", $this->getSession()->getCurrentUrl(), implode(', ', $found_headings)));
    }
  }

  /**
   * Checks multiple lines of text on the page.
   *
   * Provide data in the following format:
   * | Text 1 |
   * | Text 2 |
   * | ...    |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of texts.
   *
   * @Then I (should )see the following lines of text:
   */
  public function assertTexts(TableNode $table): void {
    $lines = $table->getColumn(0);
    $errors = [];
    foreach ($lines as $line) {
      try {
        $this->assertSession()->pageTextContains($line);
      }
      catch (ResponseTextException $e) {
        $errors[] = $line;
      }
    }
    if (!empty($errors)) {
      throw new \Exception(sprintf("The following lines of text were not found on the page %s: '%s'", $this->getSession()->getCurrentUrl(), implode(', ', $errors)));
    }
  }

  /**
   * Checks that multiple lines of text are not present on the page.
   *
   * Provide data in the following format:
   * | Text 1 |
   * | Text 2 |
   * | ...    |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of texts.
   *
   * @Then I should not see the following lines of text:
   */
  public function assertNoTexts(TableNode $table): void {
    $lines = $table->getColumn(0);
    $errors = [];
    foreach ($lines as $line) {
      try {
        $this->assertSession()->pageTextNotContains($line);
      }
      catch (ResponseTextException $e) {
        $errors[] = $line;
      }
    }
    if (!empty($errors)) {
      throw new \Exception(sprintf("The following lines of text were found on the page %s: '%s'", $this->getSession()->getCurrentUrl(), implode(', ', $errors)));
    }
  }

  /**
   * Checks multiple links on the page.
   *
   * Provide data in the following format:
   * | Link text 1 |
   * | Link text 2 |
   * | ...         |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of links.
   *
   * @Then I (should )see the following links:
   */
  public function assertLinks(TableNode $table): void {
    $links = $table->getColumn(0);
    $errors = [];
    foreach ($links as $link) {
      $element = $this->getSession()->getPage()->findLink($link);
      if (empty($element)) {
        $errors[] = $link;
      }
    }
    if (!empty($errors)) {
      throw new \Exception(sprintf("The following links were not found on the page %s: '%s'", $this->getSession()->getCurrentUrl(), implode(', ', $errors)));
    }
  }

  /**
   * Checks if multiple links are not present on the page.
   *
   * Provide data in the following format:
   * | Link text 1 |
   * | Link text 2 |
   * | ...         |
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   A list of links.
   *
   * @Then I should not see the following links:
   */
  public function assertNoLinks(TableNode $table): void {
    $links = $table->getColumn(0);
    $errors = [];
    foreach ($links as $link) {
      $element = $this->getSession()->getPage()->findLink($link);
      if (!empty($element)) {
        $errors[] = $link;
      }
    }
    if (!empty($errors)) {
      throw new \Exception(sprintf("The following links were found on the page %s: '%s'", $this->getSession()->getCurrentUrl(), implode(', ', $errors)));
    }
  }

  /**
   * Checks that the page is cacheable.
   *
   * @Then the page should be cacheable
   */
  public function assertPageCacheable(): void {
    Assert::assertTrue($this->isPageCacheable());
  }

  /**
   * Checks that the page is not cacheable.
   *
   * @Then the page should not be cacheable
   */
  public function assertPageNotCacheable(): void {
    Assert::assertFalse($this->isPageCacheable());
  }

  /**
   * Checks that the page is cached.
   *
   * @Then the page should be cached
   */
  public function assertPageCached(): void {
    Assert::assertTrue($this->isPageCached());
  }

  /**
   * Checks that the page is not cached.
   *
   * @Then the page should not be cached
   */
  public function assertPageNotCached(): void {
    Assert::assertFalse($this->isPageCached());
  }

  /**
   * Checks if a checkbox or a radio in a row with a given text is checked.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the checkbox is unchecked.
   *
   * @Then the row :text is selected/checked
   */
  public function assertRowIsChecked(string $text): void {
    if (!$this->getCheckboxOrRadioByRowText($text)->isChecked()) {
      throw new ExpectationException("Checkbox/radio-button in '$text' row is unchecked/unselected but it should be checked/selected.", $this->getSession()->getDriver());
    }
  }

  /**
   * Checks if a checkbox or a radio in a row with a given text is not checked.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If the checkbox is checked.
   *
   * @Then the row :text is not selected/checked
   */
  public function assertRowIsNotChecked(string $text): void {
    if ($this->getCheckboxOrRadioByRowText($text)->isChecked()) {
      throw new ExpectationException("Checkbox/radio-button in '$text' row is checked/selected but it should be unchecked/unselected.", $this->getSession()->getDriver());
    }
  }

  /**
   * Checks a checkbox or a radio button in a table row containing a given text.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   *
   * @Given I select/check the :text row
   */
  public function checkTableselectRow(string $text): void {
    $element = $this->getCheckboxOrRadioByRowText($text);
    if ($element->getAttribute('type') === 'checkbox') {
      $element->check();
    }
    else {
      $element->getParent()->selectFieldOption($element->getAttribute('name'), $element->getAttribute('value'));
    }
  }

  /**
   * Unchecks a checkbox or a radio in a table row containing a given text.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   * @throws \InvalidArgumentException
   *   If this step definition was used on a radio button.
   *
   * @Given I deselect/uncheck the :text row
   */
  public function uncheckTableselectRow(string $text): void {
    $element = $this->getCheckboxOrRadioByRowText($text);
    if ($element->getAttribute('type') === 'radio') {
      throw new \InvalidArgumentException("A radio button cannot be unselected.");
    }
    $element->uncheck();
  }

  /**
   * Unchecks a material checkbox in a row that contains some text.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   * @throws \InvalidArgumentException
   *   If this step definition was used on a radio button.
   *
   * @Given I uncheck the material checkbox in the :text table row
   */
  public function uncheckMaterialCheckboxInTableRow(string $text): void {
    $row = $this->getRowByRowText($text);
    $this->toggleMaterialDesignCheckbox('', $row);
  }

  /**
   * Searches the page for a row that includes the given text.
   *
   * @param string $text
   *   The text to search for.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The row element.
   */
  protected function getRowByRowText(string $text): NodeElement {
    $page = $this->getSession()->getPage();
    $rows = $page->findAll('css', 'tr');
    if (empty($rows)) {
      throw new \Exception(sprintf('No rows found on the page %s', $this->getSession()->getCurrentUrl()));
    }
    $found = FALSE;
    /** @var \Behat\Mink\Element\NodeElement $row */
    foreach ($rows as $row) {
      if (strpos($row->getText(), $text) !== FALSE) {
        $found = TRUE;
        break;
      }
    }
    if (!$found) {
      throw new \Exception(sprintf('Failed to find a row containing "%s" on the page %s', $text, $this->getSession()->getCurrentUrl()));
    }

    return $row;
  }

  /**
   * Asserts that a checkbox/radio exists in a row containing a given text.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   *
   * @Then the :text table row contains a checkbox/radio
   */
  public function assertCheckboxOrRadioExistsInRow(string $text): void {
    $this->getCheckboxOrRadioByRowText($text);
  }

  /**
   * Asserts that a checkbox/radio doesn't exists in a row with given text.
   *
   * @param string $text
   *   Text in the row.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   *
   * @Then the :text table row doesn't contain a checkbox/radio
   */
  public function assertCheckboxOrRadioNotExistsInRow(string $text): void {
    $row = $this->getRowByRowText($text);
    if ($row->find('css', 'input[type="checkbox"],input[type="radio"]')) {
      throw new ExpectationFailedException("The row '$text' contains a checkbox/radio but it should not.");
    }
  }

  /**
   * Finds a checkbox or a radio button in a table row containing a given text.
   *
   * @param string $text
   *   Text in the row.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The checkbox element.
   *
   * @throws \Exception
   *   If the page contains no rows, no row contains the text or the row
   *   contains no checkbox or radio button.
   */
  protected function getCheckboxOrRadioByRowText(string $text): NodeElement {
    $row = $this->getRowByRowText($text);
    if (!$element = $row->find('css', 'input[type="checkbox"],input[type="radio"]')) {
      throw new \Exception(sprintf('The row "%s" on the page "%s" contains no checkbox or radio button', $text, $this->getSession()->getCurrentUrl()));
    }

    return $element;
  }

  /**
   * Runs a batch operations process.
   *
   * @Given I wait for the batch process to finish
   */
  public function waitForBatchProcess(): void {
    while ($refresh = $this->getSession()
      ->getPage()
      ->find('css', 'meta[http-equiv="Refresh"]')) {
      $content = $refresh->getAttribute('content');
      $url = str_replace('0; URL=', '', $content);
      $this->getSession()->visit($url);
    }
  }

  /**
   * Clears the static cache of DatabaseCacheTagsChecksum.
   *
   * Static caches are typically cleared at the end of the request since a
   * typical web request is short lived and the process disappears when the page
   * is delivered. But if a Behat test is using DrupalContext then Drupal will
   * be bootstrapped early on (in the BeforeSuiteScope step). This starts a
   * request which is not short lived, but can live for several minutes while
   * the tests run. During the lifetime of this request there will be steps
   * executed that do requests of their own, changing the state of the Drupal
   * site. This does not however update any of the statically cached data of the
   * parent request, so this is totally unaware of the changes. This causes
   * unexpected behaviour like the failure to invalidate some caches because
   * DatabaseCacheTagsChecksum::invalidateTags() keeps a local storage of which
   * cache tags were invalidated, and this is not reset in time.
   *
   * For this reason, in such limited cases, where we need to clear the cache
   * tags cache, we tag the Behat feature with @clearStaticCache. This ensures
   * that static cache is cleared after each step.
   *
   * CAUTION: Use the @clearStaticCache tag only in scenarios where you have
   * trouble with the static caching being preserved across step requests,
   * because clearing the static cache too often might affect performance.
   *
   * @see \Drupal\Core\Cache\DatabaseCacheTagsChecksum
   * @see https://github.com/jhedstrom/drupalextension/issues/133
   *
   * @param \Behat\Behat\Hook\Scope\AfterStepScope $event
   *   The after step scope event.
   *
   * @AfterStep
   */
  public function clearCacheTagsStaticCache(AfterStepScope $event): void {
    if ($this->hasTag('clearStaticCache')) {
      parent::clearStaticCaches();
    }
  }

  /**
   * Forces the indexing of new or changed content after each step.
   *
   * When a Search API index is configured with the 'options.index_directly'
   * setting set to TRUE, the entity is not indexed immediately after was saved,
   * in hook_entity_update(), instead the indexing is postponed to the end of
   * the request. This is OK when operating manually the site, but when this is
   * wrapped in the test "page request", the index will occur only after all the
   * steps were executed and, as an effect, entities created across the steps
   * are not indexed yet when the next step is executed. For this reason, we
   * force an indexing after each step.
   *
   * @see https://www.drupal.org/project/search_api/issues/2922525
   *
   * @AfterStep
   */
  public function indexEntities(): void {
    \Drupal::service('search_api.post_request_indexing')->destruct();
  }

  /**
   * Waits until a text is dynamically added to the page.
   *
   * @param string $text
   *   The text to search for.
   *
   * @Given I wait until the page contains the text :text
   */
  public function iWaitUntilPageContains(string $text): void {
    $text = addslashes($text);
    $this->getSession()->wait(60000,
      "jQuery(':contains(\"$text\")').length > 0"
    );
  }

  /**
   * Strips elements from tables that are only readable by screen readers.
   *
   * @param \LoversOfBehat\TableExtension\Hook\Scope\AfterTableFetchScope $scope
   *   The scope class.
   *
   * @AfterTableFetch
   */
  public static function stripScreenReaderElements(AfterTableFetchScope $scope): void {
    $html_manipulator = new HtmlManipulator($scope->getHtml());
    $scope->setHtml($html_manipulator->removeElements('.visually-hidden')->html());
  }

  /**
   * Fills in the autocomplete field with the given text.
   *
   * This differs from MinkContext::fillField() in that this will no remove the
   * focus on the field after entering the text, so that the autocomplete
   * results will not disappear. The final action taken on the field will be the
   * "keyup" event for the last character.
   *
   * @param string $field
   *   The ID, name, label or value of the autocomplete field to fill in.
   * @param string $value
   *   The text to type in the autocomplete field.
   *
   * @When I type :value in the :field autocomplete field
   */
  public function fillAutoCompleteField(string $field, string $value): void {
    $this->assertJavaScriptEnabledBrowser();

    $driver = $this->getSession()->getDriver();
    if (!$driver instanceof Selenium2Driver) {
      throw new \RuntimeException("Only Selenium is currently supported for typing in autocomplete fields.");
    }

    $xpath = $this->getSession()->getSelectorsHandler()->selectorToXpath('named', ['field', $field]);
    try {
      $element = $driver->getWebDriverSession()->element('xpath', $xpath);
    }
    catch (Exception $e) {
      throw new \RuntimeException("Field with locator '$field' was not found in the page.");
    }

    // Clear any existing data in the field before typing the new data.
    $value = str_repeat(Key::BACKSPACE . Key::DELETE, strlen($element->attribute('value'))) . $value;

    // Fill in the field by directly using the postValue() method of the
    // webdriver. This executes the keystrokes that make up the text but will
    // not remove focus from the field so the autocomplete results remain
    // visible and can be inspected.
    $element->postValue(['value' => [$value]]);
  }

  /**
   * Commits the search index before starting the scenario.
   *
   * Use this in scenarios for which it is important that the search index is
   * committed before any content is created in the scenario.
   *
   * Since most scenarios start with creating some test content and this will
   * automatically commit the search index, this is only needed for tests that
   * perform asserts before creating any content of their own, since the search
   * index might still contain stale content from the previous scenario.
   *
   * @BeforeScenario @commitSearchIndex
   */
  public function commitSearchIndexBeforeScenario(): void {
    $this->commitSearchIndex();
  }

  /**
   * Installs the testing module for scenarios tagged with @errorPage.
   *
   * @BeforeScenario @errorPage
   */
  public function beforeErrorPageTesting(): void {
    static::toggleModule('install', 'error_page_test');

    // Pipe error log entries to a file rather than to standard PHP log.
    $settings = Settings::getAll();
    $settings['error_page']['log']['method'] = 3;
    $settings['error_page']['log']['destination'] = 'temporary://testing.log';
    new Settings($settings);
  }

  /**
   * Uninstalls the testing module for scenarios tagged with @errorPage.
   *
   * @AfterScenario @errorPage
   */
  public function afterErrorPageTesting(): void {
    static::toggleModule('uninstall', 'error_page_test');

    // Restore piping error log entries to the standard PHP log.
    $settings = Settings::getAll();
    unset($settings['error_page']);
    new Settings($settings);

    // Restore the site's error logging verbosity.
    $this->setSiteErrorLevel();
  }

  /**
   * Sets the site's error logging verbosity.
   *
   * @param string|null $error_level
   *   (optional) The error level. If not passed, the original error level is
   *   restored.
   *
   * @Given the site error reporting verbosity is( set to) :error_level
   */
  public function setSiteErrorLevel(string $error_level = NULL): void {
    static $original_error_level;

    $config = \Drupal::configFactory()->getEditable('system.logging');

    $current_error_level = $config->get('error_level');
    if (!isset($original_error_level)) {
      $original_error_level = $current_error_level;
    }

    $error_level = $error_level ?: $original_error_level;
    if ($current_error_level !== $error_level) {
      static::bypassReadOnlyConfig();
      $config->set('error_level', $error_level)->save();
      static::restoreReadOnlyConfig();
    }
  }

  /**
   * Navigates to the canonical page of a taxonomy term with a given format.
   *
   * @param string $vocabulary_name
   *   The name of the vocabulary.
   * @param string $term_name
   *   The term name.
   * @param string $format
   *   The RDF serialization format.
   *
   * @Given I visit the :vocabulary_name term :term_name page in the :format serialisation
   */
  public function visitTermWithFormat(string $vocabulary_name, string $term_name, string $format): void {
    /** @var \Drupal\taxonomy\Entity\Vocabulary $vocabulary */
    $vocabulary = $this->getEntityByLabel('taxonomy_vocabulary', $vocabulary_name);
    $term = $this->getEntityByLabel('taxonomy_term', $term_name, $vocabulary->id());
    $this->visitPath($term->toUrl('canonical', ['query' => ['_format' => $format]])->toString());
  }

  /**
   * Disables the Antibot functionality during tests run.
   *
   * Antibot module blocks all form submissions the for browsers without
   * JavaScript support or when there's no keyboard or mouse interaction before
   * the form is submitted. This would make most of Behat tests to fail. We
   * disable Antibot functionality during Behat tests run.
   *
   * @BeforeSuite
   */
  public static function disableAntibotForSuite(): void {
    static::disableAntibot();
  }

  /**
   * Restores the Antibot functionality after tests run.
   *
   * @AfterSuite
   *
   * @see self::disableAntibotForSuite()
   */
  public static function restoreAntibotForSuite(): void {
    static::restoreAntibot();
  }

  /**
   * Restores Antibot functionality in the scope of @antibot tagged scenarios.
   *
   * The Antibot functionality is disabled for the whole test suite run, in
   * self::disableAntibotForSuite(). However, if a scenario wants run its test
   * with Antibot functionality enabled, it should be tagged with @antibot.
   *
   * @BeforeScenario @antibot
   *
   * @see self::disableAntibotForSuite()
   */
  public function restoreAntibotForScenario(): void {
    self::restoreAntibot();
  }

  /**
   * Disables Antibot functionality after @antibot tagged scenarios.
   *
   * @AfterScenario @antibot
   *
   * @see self::restoreAntibotForScenario()
   */
  public function disableAntibotForScenario(): void {
    static::disableAntibot();
  }

  /**
   * Installs/uninstalls a module in tests.
   *
   * @param string $method
   *   Either 'install' or 'uninstall'.
   * @param string $module_name
   *   The module to be installed/uninstalled.
   */
  protected static function toggleModule(string $method, string $module_name): void {
    // Ensure that test modules are also discoverable.
    $settings = ['extension_discovery_scan_tests' => TRUE] + Settings::getAll();
    new Settings($settings);

    static::bypassReadOnlyConfig();
    \Drupal::service('module_installer')->$method([$module_name]);
    static::restoreReadOnlyConfig();
  }

  /**
   * Checks if the current form is protected by Antibot.
   *
   * @throws \Exception
   *   When the expectancy is not met.
   *
   * @Then the form is protected by Antibot
   */
  public function assertFormIsProtectedByAntibot(): void {
    $session = $page = $this->getSession();

    // Unlock the form by using the Antibot javascript API.
    $session->executeScript('Drupal.antibot.unlockForms();');

    $has_js_assigned_value = (bool) $session->getPage()->find('xpath', '//form[@data-action]//input[@data-drupal-selector="edit-antibot-key" and @name="antibot_key" and string(@value)]');
    if (!$has_js_assigned_value) {
      throw new \Exception("Not an Antibot protected form.");
    }
  }

  /**
   * Cleans up the existing list of entities before the scenario starts.
   *
   * @BeforeScenario @messageCleanup
   */
  public function cleanupMessageEntities(): void {
    $message_storage = \Drupal::entityTypeManager()->getStorage('message');
    $mids = $message_storage->getQuery()->execute();
    $message_storage->delete($message_storage->loadMultiple($mids));
  }

  /**
   * Creates a backup of the Joinup `VERSION` file.
   *
   * Tests that interact with the version file should be tagged with `@version`.
   *
   * @BeforeScenario @version
   */
  public function backupJoinupVersion(): void {
    $filename = DRUPAL_ROOT . '/../VERSION';
    $this->version = file_exists($filename) ? $this->version = file_get_contents($filename) : FALSE;
  }

  /**
   * Restores the backup of the Joinup `VERSION` file.
   *
   * @AfterScenario @version
   */
  public function restoreJoinupVersion(): void {
    if ($this->version === FALSE) {
      unlink(JoinupVersionInterface::PATH);
    }
    else {
      file_put_contents(JoinupVersionInterface::PATH, $this->version);
    }
  }

  /**
   * Sets the Joinup version.
   *
   * Since this overwrites the `VERSION` file in the root folder, any scenario
   * that includes this step should be tagged with `@version` so that the
   * original contents of the file will be restored at the end of the scenario.
   *
   * @param string $version
   *   The Joinup version to set, e.g. 'v1.57.0' or 'v1.57.0-66-g1234abcde'.
   *
   * @When the Joinup version is set to :version
   */
  public function setJoinupVersion(string $version): void {
    // Alert the user that the `@version` tag is required.
    Assert::assertTrue($this->hasTag('version'), 'The `@version` tag is required for scenarios that want to change the Joinup version.');

    // We also require the Drupal API to retrieve the project root folder.
    Assert::assertTrue($this->hasTag('api'), 'The `@api` tag is required for scenarios that use the `@version` tag.');

    file_put_contents(JoinupVersionInterface::PATH, $version);

    // The version string is not meant to change in between deployments, so it
    // doesn't employ a cache context. In order to make the version show up on
    // previously cached pages we need to invalidate the render cache manually.
    Cache::invalidateTags(['rendered']);
  }

  /**
   * Asserts that a file downloaded from a link contains a list of strings.
   *
   * IMPORTANT NOTE: This step definition is not performing any file access
   * check. The file content is read directly from the file system. The user
   * access to the file should be tested in separate Behat steps.
   *
   * @param string $link_label
   *   The link from where to download the file.
   * @param \Behat\Gherkin\Node\TableNode $strings_table
   *   A table with a single column. Each row contains a string.
   *
   * @throws \Exception
   *   If the link has no href attribute or the file content cannot be loaded.
   *
   * @Then the file downloaded from the :link_label link contains the following strings:
   */
  public function assertDownloadedFileContainsStrings(string $link_label, TableNode $strings_table): void {
    if (!$link = $this->getSession()->getPage()->findLink($link_label)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'Link', NULL, $link_label);
    }
    if (!$url = $link->getAttribute('href')) {
      throw new \Exception("The link '${link_label}' misses an 'href' attribute.");
    }

    // Get the path part from the URL.
    $path = trim(parse_url($url, PHP_URL_PATH), '/');

    // Drupal private file.
    if (strpos($path, 'system/files') === 0) {
      $path = Settings::get('file_private_path') . '/' . substr($path, 12);
    }
    // Webserver accessible file.
    else {
      $path = DRUPAL_ROOT . "/{$path}";
    }

    if (($content = file_get_contents($path)) === FALSE) {
      throw new \Exception("Cannot read '{$path}' file.");
    }

    if (!$content) {
      throw new \Exception("The downloaded file has no content.");
    }

    $not_found = array_filter($strings_table->getColumn(0), function (string $text) use ($content): bool {
      return strpos($content, $text) === FALSE;
    });

    if ($not_found) {
      throw new ExpectationFailedException("Following strings were not found in the downloaded file:\n- " . implode("\n- ", $not_found));
    }
  }

  /**
   * Stores the ID of the latest file entity created before the scenario.
   *
   * @beforeFeature
   */
  public static function storeLastFileId(): void {
    static::$lastFileId = \Drupal::database()->query("SELECT MAX(fid) FROM {file_managed}")->fetchField() ?: 0;
  }

  /**
   * Removes files created during test scenarios.
   *
   * Since Drupal 8.4.0, files that have no remaining usages are no longer
   * deleted by default, see https://www.drupal.org/node/2891902. Even the host
   * entities are deleted after test, the files attached via UI are not cleared
   * at the end of the test scenario. This might cause some scenarios, creating
   * the same file, to fail because the file will get a different, incremental,
   * file base name. Note that files created via API are handled in
   * FileTrait::cleanFiles().
   *
   * @see https://www.drupal.org/node/2891902
   * @see \Drupal\joinup\Traits\FileTrait::cleanFiles()
   *
   * @afterFeature
   */
  public static function staleFilesCleanup(): void {
    $fids = \Drupal::database()->query("SELECT fid FROM {file_managed} WHERE fid > :fid", [':fid' => static::$lastFileId])->fetchCol();
    if ($fids) {
      /** @var \Drupal\file\FileStorageInterface $storage */
      $storage = \Drupal::entityTypeManager()->getStorage('file');
      $storage->delete($storage->loadMultiple($fids));
    }
  }

  /**
   * Switch to Behat specific Drupal settings during the test suite.
   *
   * @beforeSuite
   */
  public static function addBehatSpecificDrupalSettings(): void {
    static::runCommand('drupal:settings behat --root=' . static::getPath('web') . ' --sites-subdir=default');
  }

  /**
   * Restore the original Drupal settings.
   *
   * @afterSuite
   */
  public static function restoreDrupalSettings(): void {
    static::runCommand('drupal:settings site-clean --root=' . static::getPath('web') . ' --sites-subdir=default');
  }

}

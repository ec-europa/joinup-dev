<?php

/**
 * @file
 * Contains step definitions for the Joinup project.
 */

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\Component\Serialization\Yaml;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\ContextualLinksTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;

/**
 * Defines generic step definitions.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  use BrowserCapabilityDetectionTrait;
  use ContextualLinksTrait;
  use EntityTrait;
  use TraversingTrait;
  use UtilityTrait;

  /**
   * Define ASCII values for key presses.
   */
  const KEY_LEFT = 37;
  const KEY_RIGHT = 39;

  /**
   * Checks that a 403 Access Denied error occurred.
   *
   * @Then I should get an access denied error
   */
  public function assertAccessDenied() {
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Assert that certain fields are present on the page.
   *
   * @param string $fields
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when an expected field is not present.
   *
   * @Then (the following )fields should be present :fields
   */
  public function assertFieldsPresent($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    $not_found = [];
    foreach ($fields as $field) {
      $is_found = $page->findField($field);
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
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when a column name is incorrect.
   *
   * @Then (the following )fields should not be present :fields
   */
  public function assertFieldsNotPresent($fields) {
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
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when an expected field is not present or is not visible.
   *
   * @Then (the following )fields should be visible :fields
   */
  public function assertFieldsVisible($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    $not_found = [];
    $not_visible = [];
    foreach ($fields as $field) {
      $element = $page->findField($field);
      if (!$element) {
        $not_found[] = $field;
        continue;
      }
      elseif (!$element->isVisible()) {
        // Retrieve the first standard form item wrapper around our field.
        // Some fields, like text areas or checkboxes, are actually hidden but
        // their label and container are not.
        $wrapper = $element->find('xpath', "ancestor-or-self::div[@class and contains(concat(' ', normalize-space(@class), ' '), ' form-item ')][1]");

        if (!$wrapper->isVisible()) {
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
   *    Fields.
   *
   * @throws \Exception
   *   Thrown when a field is not present or is visible.
   *
   * @Then (the following )fields should not be visible :fields
   */
  public function assertFieldsNotVisible($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $page = $this->getSession()->getPage();
    $not_found = [];
    $visible = [];
    foreach ($fields as $field) {
      $element = $page->findField($field);
      if (!$element) {
        $not_found[] = $field;
        continue;
      }

      // Retrieve the first standard form item wrapper around our field.
      // Some fields, like text areas or checkboxes, are actually hidden but
      // their label and container are not.
      $wrapper = $element->find('xpath', "ancestor-or-self::div[@class and contains(concat(' ', normalize-space(@class), ' '), ' form-item ')][1]");
      // Neither the field or its wrapper should be visible at all.
      if ($element->isVisible() || $wrapper->isVisible()) {
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
   *    The fieldset names to search for, separated by comma.
   *
   * @throws \Exception
   *   Thrown when a fieldset is not found.
   *
   * @Then (the following )field widgets should be present :fieldsets
   * @Then (the following )fieldsets should be present :fieldsets
   */
  public function assertFieldsetsPresent($fieldsets) {
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
   *    The fieldset names to search for, separated by comma.
   *
   * @throws \Exception
   *   Thrown when a fieldset is not found or is not visible.
   *
   * @Then (the following )field widgets should be visible :fieldsets
   * @Then (the following )fieldsets should be visible :fieldsets
   */
  public function assertFieldsetsVisible($fieldsets) {
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
   *    The fieldset names to search for, separated by comma.
   *
   * @throws \Exception
   *   Thrown when a fieldset is not found or is visible.
   *
   * @Then (the following )field widgets should not be visible :fieldsets
   * @Then (the following )fieldsets should not be visible :fieldsets
   */
  public function assertFieldsetsNotVisible($fieldsets) {
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
   * @Then I (should )see the image :filename
   */
  public function assertImagePresent($filename) {
    // Drupal appends an underscore and a number to the filename when duplicate
    // files are uploaded, for example when a test is run more than once.
    // We split up the filename and extension and match for both.
    $parts = pathinfo($filename);
    $extension = $parts['extension'];
    $filename = $parts['filename'];
    $this->assertSession()->elementExists('css', "img[src$='.$extension'][src*='$filename']");
  }

  /**
   * Checks that a given image is not present in the page.
   *
   * @Then I should not see the image :filename
   */
  public function assertImageNotPresent($filename) {
    // Drupal appends an underscore and a number to the filename when duplicate
    // files are uploaded, for example when a test is run more than once.
    // We split up the filename and extension and match for both.
    $parts = pathinfo($filename);
    $extension = $parts['extension'];
    $filename = $parts['filename'];
    $this->assertSession()->elementNotExists('css', "img[src$='.$extension'][src*='$filename']");
  }

  /**
   * Maximize the browser window for javascript tests so elements are visible.
   *
   * @Given I maximize the browser window
   */
  public function maximizeBrowserWindow() {
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
  public function assertOptionSelected($text) {
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
  public function assertOptionNotSelected($text) {
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
   *
   * @Then the option with text :option from select :select is selected
   */
  public function assertFieldOptionSelected($option, $select) {
    $selectField = $this->getSession()->getPage()->find('css', $select);
    if ($selectField === NULL) {
      throw new \Exception(sprintf('The select "%s" was not found in the page %s', $select, $this->getSession()->getCurrentUrl()));
    }

    $optionField = $selectField->find('xpath', '//option[@selected="selected"]');
    if ($optionField === NULL) {
      throw new \Exception(sprintf('No option is selected in the %s select in the page %s', $select, $this->getSession()->getCurrentUrl()));
    }

    if ($optionField->getHtml() != $option) {
      throw new \Exception(sprintf('The option "%s" was not selected in the page %s, %s was selected', $option, $this->getSession()->getCurrentUrl(), $optionField->getHtml()));
    }
  }

  /**
   * Checks that a certain radio input is checked in a specific field.
   *
   * @param string $radio
   *   The label of the radio input to find.
   * @param string $field
   *   The label of the field the radio is part of.
   *
   * @throws \Exception
   *   Thrown when the field or the radio is not found, or if the radio is not
   *   checked.
   *
   * @Then the radio :radio from field :field should be checked
   */
  public function assertFieldRadioSelected($radio, $field) {
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
      throw new \Exception("The radio '$radio' is not checked.");
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
   *
   * @Then the option with text :option from select :select is not selected
   */
  public function assertFieldOptionNotSelected($option, $select) {
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
  public function assertContentPageByTitle($type, $title) {
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
  public function assertUserExistence($username) {
    $user = user_load_by_name($username);

    if (empty($user)) {
      throw new \Exception("Unable to load expected user " . $username);
    }
  }

  /**
   * Click on an element by css class.
   *
   * @Then /^I click on element "([^"]*)"$/
   */
  public function iClickOn($element) {
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
  public function iClickTheContextualLinkInTheRegion($text, $region) {
    $links = $this->findContextualLinksInRegion($region);

    if (!isset($links[$text])) {
      throw new \Exception(t('Could not find a contextual link %link in the region %region', ['%link' => $text, '%region' => $region]));
    }

    $this->getSession()->visit($this->locatePath($links[$text]));
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
  public function assertContextualLinkInRegionPresent($text, $region) {
    $links = $this->findContextualLinksInRegion($region);

    if (!isset($links[$text])) {
      throw new \Exception(t('Contextual link %link expected but not found in the region %region', ['%link' => $text, '%region' => $region]));
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
   * @Then I (should )not see the contextual link :text in the :region region
   */
  public function assertContextualLinkInRegionNotPresent($text, $region) {
    $links = $this->findContextualLinksInRegion($region);

    if (isset($links[$text])) {
      throw new \Exception(t('Unexpected contextual link %link found in the region %region', ['%link' => $text, '%region' => $region]));
    }
  }

  /**
   * Moves a slider to the next or previous option.
   *
   * @param string $label
   *   The label of the slider that will be fingered.
   * @param string $direction
   *   The direction in which the slider will be moved. Can be either 'left' or
   *   'right'.
   *
   * @throws \Exception
   *   Thrown when the slider could not be found in the page, or when an invalid
   *   direction is passed.
   *
   * @When I move the :label slider to the :direction
   */
  public function moveSlider($label, $direction) {
    // Check that the direction is either 'left' or 'right'.
    if (!in_array($direction, ['left', 'right'])) {
      throw new \Exception("The direction $direction is currently not supported. Use either 'left' or 'right'.");
    }
    $key = $direction === 'left' ? static::KEY_LEFT : static::KEY_RIGHT;

    // Locate the slider starting from the label:
    // - Find the label with the given label text.
    // - Move up the DOM to the wrapper div of the select element. This is
    //   identified by the class 'form-type-select'.
    // - In this wrapper, find the slider handle, this is a span with class
    //   'ui-slider-handle'.
    $xpath = '//label[text()="' . $label . '"]/ancestor::div[contains(concat(" ", normalize-space(@class), " "), " form-type-select ")]//span[contains(concat(" ", normalize-space(@class), " "), " ui-slider-handle ")]';
    $slider = $this->getSession()->getPage()->find('xpath', $xpath);

    if (!$slider) {
      throw new \Exception("Slider with label $label not found in the page.");
    }

    // Focus the slider handle, and move it. Note that we are using the keyboard
    // to move the slider instead of the mouse. This ensures that this works
    // fine at all slider widths and screen sizes.
    $slider->focus();
    $slider->keyDown($key);
    $slider->keyUp($key);
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
  public function assertContextualLinkButtonVisible($region) {
    $button = $this->findContextualLinkButtonInRegion($region);
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
  public function clickContextualLinkButton($region) {
    $button = $this->findContextualLinkButtonInRegion($region);
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
   * @Then the :locator :element in the :region( region) should not be visible
   */
  public function assertElementNotVisibleInRegion($locator, $element, $region) {
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
   * @Then the :locator :element in the :region( region) should be visible
   */
  public function assertElementVisibleInRegion($locator, $element, $region) {
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
  public function clickVerticalTabLink($tab) {
    // When this is running in a browser without JavaScript the vertical tabs
    // are rendered as a details element.
    if (!$this->browserSupportsJavascript()) {
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
  public function assertVerticalTabActive($tab) {
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
  public function provideTestingTerms() {
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
  public function fillFieldWithValues($field, $values) {
    $values = $this->explodeCommaSeparatedStepArgument($values);

    /** @var \Behat\Mink\Element\NodeElement[] $items */
    $items = $this->getSession()->getPage()->findAll('named', array('field', $field));

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

}

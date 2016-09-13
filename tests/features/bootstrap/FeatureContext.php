<?php

/**
 * @file
 * Contains step definitions for the Joinup project.
 */

use Behat\Behat\Context\SnippetAcceptingContext;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines generic step definitions.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

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
   * @Then /^(?:|the following )fields should be present? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsPresent($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $not_found = [];
    foreach ($fields as $field) {
      $is_found = $this->getSession()->getPage()->find('named', ['field', $field]);
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
   * @Then /^(?:|the following )fields should not be present? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsNotPresent($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    foreach ($fields as $field) {
      $is_found = $this->getSession()->getPage()->find('named', ['field', $field]);
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
   * @Then /^(?:|the following )fields should be visible? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsVisible($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $not_found = [];
    $not_visible = [];
    foreach ($fields as $field) {
      $element = $this->getSession()->getPage()->find('named', ['field', $field]);
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
   * @Then /^(?:|the following )fields should not be visible? "(?P<fields>[^"]*)"$/
   */
  public function assertFieldsNotVisible($fields) {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $not_found = [];
    $visible = [];
    foreach ($fields as $field) {
      $element = $this->getSession()->getPage()->find('named', ['field', $field]);
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
    $not_found = [];
    foreach ($fieldsets as $fieldset) {
      $is_found = $this->getSession()->getPage()->find('named', ['fieldset', $fieldset]);
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
    $not_found = [];
    $not_visible = [];
    foreach ($fieldsets as $fieldset) {
      $is_found = $this->getSession()->getPage()->find('named', ['fieldset', $fieldset]);
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
    $not_found = [];
    $visible = [];
    foreach ($fieldsets as $fieldset) {
      $is_found = $this->getSession()->getPage()->find('named', ['fieldset', $fieldset]);
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
    else {
      $findName->click();
    }
  }

  /**
   * Resolves contextual links directly, without the need for javascript.
   *
   * @Then I click the contextual link :linkText in the :region region
   */
  public function iClickTheContextualLinkInTheRegion($linkText, $region) {
    $account = user_load($this->getUserManager()->getCurrentUser()->uid);
    $links = array();
    /** @var \Drupal\Core\Menu\ContextualLinkManager $contextual_links_manager */
    $contextual_links_manager = \Drupal::service('plugin.manager.menu.contextual_link');
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    if (!$regionObj) {
      throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
    }
    /** @var \Behat\Mink\Element\NodeElement $item */
    foreach ($regionObj->findAll('xpath', '//*[@data-contextual-id]') as $item) {
      $contextual_id = $item->getAttribute('data-contextual-id');
      foreach (_contextual_id_to_links($contextual_id) as $group_name => $link) {
        $route_parameters = $link['route_parameters'];
        foreach ($contextual_links_manager->getContextualLinkPluginsByGroup($group_name) as $plugin_id => $plugin_definition) {
          /** @var \Drupal\Core\Menu\ContextualLinkInterface $plugin */
          $plugin = $contextual_links_manager->createInstance($plugin_id);
          $route_name = $plugin->getRouteName();
          // Check access.
          if (!\Drupal::accessManager()->checkNamedRoute($route_name, $route_parameters, $account)) {
            continue;
          }
          /** @var \Drupal\Core\Url $url */
          $url = Url::fromRoute($route_name, $route_parameters, $plugin->getOptions())->toRenderArray();
          $links[$plugin->getTitle()] = $url['#url']->toString();
        }
      }
    }
    if (isset($links[$linkText])) {
      $session->visit($this->locatePath($links[$linkText]));
      return;
    }
    throw new \Exception(t('Could not find a contextual link %link in the region %region', ['%link' => $linkText, '%region' => $region]));
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
   * Explodes and sanitizes a comma separated step argument.
   *
   * @param string $argument
   *   The string argument.
   *
   * @return array
   *   The argument as array, with trimmed non-empty values.
   */
  protected function explodeCommaSeparatedStepArgument($argument) {
    $argument = explode(',', $argument);
    $argument = array_map('trim', $argument);
    $argument = array_filter($argument);

    return $argument;
  }

  /**
   * Returns the entity with the given type, bundle and label.
   *
   * If multiple entities have the same label then the first one is returned.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $label
   *   The label to check.
   * @param string $bundle
   *   Optional bundle to check. If omitted, the entity can be of any bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The requested entity.
   *
   * @throws \Exception
   *   Thrown when an entity with the given type, label and bundle does not
   *   exist.
   */
  protected function getEntityByLabel($entity_type, $label, $bundle = NULL) {
    $entity_manager = \Drupal::entityTypeManager();
    $storage = $entity_manager->getStorage($entity_type);
    $entity = $entity_manager->getDefinition($entity_type);

    $query = $storage->getQuery()
      ->condition($entity->getKey('label'), $label)
      ->range(0, 1);

    // Optionally filter by bundle.
    if ($bundle) {
      $query->condition($entity->getKey('bundle'), $bundle);
    }

    $result = $query->execute();

    if ($result) {
      $result = reset($result);
      return $storage->load($result);
    }

    throw new \Exception("The entity with label '$label' was not found.");
  }

}

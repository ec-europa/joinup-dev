<?php

/**
 * @file
 * Contains step definitions for the Joinup project.
 */

use Behat\Behat\Context\SnippetAcceptingContext;
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

}

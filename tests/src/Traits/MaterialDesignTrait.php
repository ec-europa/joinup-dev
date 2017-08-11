<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\TraversableElement;

/**
 * Helper methods for interacting with material design elements.
 */
trait MaterialDesignTrait {

  use BrowserCapabilityDetectionTrait;

  /**
   * Checks the materially designed checkbox with the given label.
   *
   * In Material Design regular checkboxes are hidden and replaces with fancy
   * animated fake checkboxes. This method can check them.
   *
   * On browsers without JavaScript this falls back to the standard behaviour.
   *
   * @param string $label
   *   The label of the field to check.
   * @param \Behat\Mink\Element\TraversableElement $element
   *   Element in which to search for the field label.
   *
   * @throws \Exception
   *   Thrown when the animated checkbox or the hidden input field with the
   *   given label is not found.
   */
  protected function checkMaterialDesignField($label, TraversableElement $element) {
    if ($this->browserSupportsJavascript()) {
      // Check if the checkbox has already been checked.
      if (!$this->findMaterialDesignCheckbox($label, $element)->isChecked()) {
        $this->toggleMaterialDesignCheckbox($label, $element);
      }
    }
    // Fall back to the standard behaviour if JavaScript is disabled.
    else {
      $element->checkField($label);
    }
  }

  /**
   * Unchecks the materially designed checkbox with the given label.
   *
   * In Material Design regular checkboxes are hidden and replaces with fancy
   * animated fake checkboxes. This method can uncheck them.
   *
   * On browsers without JavaScript this falls back to the standard behaviour.
   *
   * @param string $label
   *   The label of the field to uncheck.
   * @param \Behat\Mink\Element\TraversableElement $element
   *   Element in which to search for the field label.
   *
   * @throws \Exception
   *   Thrown when the animated checkbox or the hidden input field with the
   *   given label is not found.
   */
  protected function uncheckMaterialDesignField($label, TraversableElement $element) {
    if ($this->browserSupportsJavascript()) {
      // Only check if the checkbox is unchecked.
      if ($this->findMaterialDesignCheckbox($label, $element)->isChecked()) {
        $this->toggleMaterialDesignCheckbox($label, $element);
      }
    }
    // Fall back to the standard behaviour if JavaScript is disabled.
    else {
      $element->uncheckField($label);
    }
  }

  /**
   * Toggles (clicks) the materially designed checkbox with the given label.
   *
   * In Material Design regular checkboxes are hidden and replaces with fancy
   * animated fake checkboxes. This method can toggle them.
   *
   * @param string $label
   *   The label of the animated checkbox to toggle.
   * @param \Behat\Mink\Element\TraversableElement $element
   *   Element in which to search for the field label.
   *
   * @throws \Exception
   *   Thrown when the browser does not support JavaScript or when the animated
   *   checkbox with the given label is not found.
   */
  protected function toggleMaterialDesignCheckbox($label, TraversableElement $element) {
    if (!$this->browserSupportsJavascript()) {
      throw new \Exception("The animated checkbox with label $label cannot be toggled in a browser that doesn't support JavaScript.");
    }

    // Locate the "fancy" checkbox and click it.
    $checkbox_xpath = '//label[text()="' . $label . '"]/../../span[contains(concat(" ", normalize-space(@class), " "), " mdl-checkbox__ripple-container ")]';
    $checkbox_element = $element->find('xpath', $checkbox_xpath);
    if (empty($checkbox_element)) {
      throw new \Exception("The animated checkbox for the $label field was not found in the page.");
    }
    $checkbox_element->click();
  }

  /**
   * Returns the input field for an animated checkbox with the given label.
   *
   * In Material Design regular checkboxes are hidden and replaces with fancy
   * animated fake checkboxes. This method can find them.
   *
   * @param string $label
   *   The label of the animated checkbox to toggle.
   * @param \Behat\Mink\Element\TraversableElement $element
   *   Element in which to search for the field label.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The input field.
   *
   * @throws \Exception
   *   Thrown when the browser does not support JavaScript or when the animated
   *   checkbox with the given label is not found.
   */
  protected function findMaterialDesignCheckbox($label, TraversableElement $element) {
    if (!$this->browserSupportsJavascript()) {
      throw new \Exception("The hidden input field for the $label checkbox cannot be found in a browser that doesn't support JavaScript.");
    }

    $input_xpath = '//label[text()="' . $label . '"]/../../input[contains(concat(" ", normalize-space(@class), " "), " mdl-checkbox__input ")]';
    $input_element = $element->find('xpath', $input_xpath);
    if (empty($input_element)) {
      throw new \Exception("The hidden input field for the $label checkbox was not found in the page.");
    }

    return $input_element;
  }

  /**
   * Opens a MDL menu on JS-enabled browsers.
   *
   * @param \Behat\Mink\Element\NodeElement $wrapper
   *   The element that contains the MDL menu.
   *
   * @throws \Exception
   *   Thrown when the menu wrapper or menu button are not found in the page,
   *   and when the menu doesn't become visible within the allowed time frame.
   */
  protected function openMaterialDesignMenu(NodeElement $wrapper) {
    if ($this->browserSupportsJavascript()) {
      if (!$wrapper) {
        throw new \Exception('The MDL menu wrapper was not found in the page.');
      }

      $button = $wrapper->find('xpath', '//button');
      if (!$button) {
        throw new \Exception('The MDL menu button was not found in the page.');
      }

      // The button ID is used in the "for" attribute of the related menu.
      // Create the xpath that targets the last direct child "li" element, as
      // that will be the last one appearing with the MDL animation.
      $button_id = $button->getAttribute('id');
      $last_li_xpath = $wrapper->find('xpath', "//ul[@for and @for='{$button_id}']/li[last()]")->getXpath();
      $button->click();

      // Wait for the menu opening animation to end before continuing.
      $end = microtime(TRUE) + 5;
      $driver = $this->getSession()->getDriver();
      do {
        usleep(100000);
        // The plus button opening animation runs from the top right to the
        // bottom left. Wait for the last element to become visible to ensure
        // the menu is fully opened.
        $visible = $driver->isVisible($last_li_xpath);
      } while (microtime(TRUE) < $end && !$visible);

      if (!$visible) {
        throw new \Exception('The MDL menu did not open properly within the expected timeframe.');
      }
    }
  }

}

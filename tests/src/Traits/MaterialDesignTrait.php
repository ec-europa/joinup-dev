<?php

namespace Drupal\joinup\Traits;

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

}

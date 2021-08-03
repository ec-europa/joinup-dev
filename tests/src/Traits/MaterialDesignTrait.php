<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\TraversableElement;

/**
 * Helper methods for interacting with material design elements.
 */
trait MaterialDesignTrait {

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
    \assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    if ($this->browserSupportsJavaScript()) {
      // Check if the checkbox has already been checked.
      if (!$this->findMaterialDesignCheckbox($label, $element)->isChecked()) {
        $this->toggleMaterialDesignCheckbox($element, $label);
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
    \assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    if ($this->browserSupportsJavaScript()) {
      // Only check if the checkbox is unchecked.
      if ($this->findMaterialDesignCheckbox($label, $element)->isChecked()) {
        $this->toggleMaterialDesignCheckbox($element, $label);
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
   * @param \Behat\Mink\Element\TraversableElement $element
   *   Element in which to search for the field label.
   * @param string|null $label
   *   The label of the animated checkbox to toggle.
   *
   * @throws \Exception
   *   Thrown when the browser does not support JavaScript or when the animated
   *   checkbox with the given label is not found.
   */
  protected function toggleMaterialDesignCheckbox(TraversableElement $element, ?string $label = NULL): void {
    \assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    if (!$this->browserSupportsJavaScript()) {
      throw new \Exception("The animated checkbox with label $label cannot be toggled in a browser that doesn't support JavaScript.");
    }

    if (empty($label)) {
      $checkbox_xpath = '//span[contains(concat(" ", normalize-space(@class), " "), " mdl-checkbox__ripple-container ")]';
    }
    else {
      $checkbox_xpath = '//label[text()="' . $label . '"]/../../span[contains(concat(" ", normalize-space(@class), " "), " mdl-checkbox__ripple-container ")]';
    }
    // Locate the "fancy" checkbox and click it.
    $checkbox_element = $element->find('xpath', $checkbox_xpath);
    if (empty($checkbox_element)) {
      throw new \Exception("The animated checkbox for the $label field was not found in the page.");
    }
    $checkbox_element->click();
  }

  /**
   * Returns the input field for an animated checkbox with the given label.
   *
   * In Material Design regular checkboxes are hidden and replaced with fancy
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
    \assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    if (!$this->browserSupportsJavaScript()) {
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
   * @param null|\Behat\Mink\Element\NodeElement $wrapper
   *   The element that contains the MDL menu.
   *
   * @throws \Exception
   *   Thrown when the menu wrapper or menu button are not found in the page,
   *   and when the menu doesn't become visible within the allowed time frame.
   */
  protected function openMaterialDesignMenu($wrapper) {
    \assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    if ($this->browserSupportsJavaScript()) {
      if (!$wrapper) {
        throw new \Exception('The MDL menu wrapper was not found in the page.');
      }

      $button = $wrapper->find('css', '.mdl-button');
      if (!$button) {
        throw new \Exception('The MDL menu button was not found in the page.');
      }

      $last_li_xpath = $wrapper->find('xpath', "//ul/li[last()]")->getXpath();
      $driver = $this->getSession()->getDriver();
      if ($driver->isVisible($last_li_xpath)) {
        // Since the browser window size can vary in different test environments
        // and some menus have a different behavior depending on the browser
        // width, the menu might already be open.
        // In these cases, the press of the button would change the already
        // proper visibility state of the menu items. Prevent this behavior by
        // returning early if the menu items are already visible.
        return;
      }

      $button->click();

      // Wait for the menu opening animation to end before continuing.
      $end = microtime(TRUE) + 5;
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

  /**
   * Selects the materially designed radio button with the given label.
   *
   * In Material Design regular radio buttons are hidden and replaced by fancy
   * animated fake radio buttons. This method can select them.
   *
   * Only supports browsers with JavaScript support at the moment.
   *
   * @param string $label
   *   The label of the radio button to select.
   * @param \Behat\Mink\Element\TraversableElement $parent_element
   *   Parent element in which to search for the radio button.
   *
   * @throws \Exception
   *   Thrown when the radio button is not found.
   */
  protected function selectMaterialDesignRadioButton(string $label, TraversableElement $parent_element): void {
    \assert(method_exists($this, 'browserSupportsJavaScript'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    \assert($this->browserSupportsJavaScript(), 'A fallback method to select a material design radio button has not yet been implemented for non-JS browsers.');

    $xpath = '//label[text()="' . $label . '"]/../../span[contains(concat(" ", normalize-space(@class), " "), " mdl-radio__ripple-container ")]';

    // Locate the "fancy" radio button and click it.
    $fancy_button = $parent_element->find('xpath', $xpath);
    if (empty($fancy_button)) {
      throw new \Exception("The radio button labelled $label was not found.");
    }
    $fancy_button->click();

    // Wait until the animation completes and the radio button is selected.
    $xpath = '//label[text()="' . $label . '"]/../parent::label[contains(concat(" ", normalize-space(@class), " "), " is-checked ")]/span[contains(concat(" ", normalize-space(@class), " "), " mdl-radio__ripple-container ")]';
    $end = microtime(TRUE) + 10;
    do {
      usleep(100000);
      // The plus button opening animation runs from the top right to the
      // bottom left. Wait for the last element to become visible to ensure
      // the menu is fully opened.
      $animation_finished = !empty($parent_element->find('xpath', $xpath));
    } while (microtime(TRUE) < $end && !$animation_finished);

    if (!$animation_finished) {
      throw new \Exception("Waited 5 seconds after selecting the '$label' radio button but the animation didn't finish.");
    }
  }

}

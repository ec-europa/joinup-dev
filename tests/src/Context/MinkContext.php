<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\MinkContext as DrupalExtensionMinkContext;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use PHPUnit\Framework\ExpectationFailedException;

/**
 * Provides step definitions for interacting with Mink.
 */
class MinkContext extends DrupalExtensionMinkContext {

  use BrowserCapabilityDetectionTrait;
  use MaterialDesignTrait;

  /**
   * {@inheritdoc}
   */
  public function checkOption($option) {
    // Overrides the default method for checking checkboxes to make it
    // compatible with material design.
    $option = $this->fixStepArgument($option);
    $this->checkMaterialDesignField($option, $this->getSession()->getPage());
  }

  /**
   * {@inheritdoc}
   */
  public function uncheckOption($option) {
    // Overrides the default method for unchecking checkboxes to make it
    // compatible with material design.
    $option = $this->fixStepArgument($option);
    $this->uncheckMaterialDesignField($option, $this->getSession()->getPage());
  }

  /**
   * {@inheritdoc}
   *
   * @Then the :radio radio button should be selected
   */
  public function assertCheckboxChecked($radio) {
    // We're just adding a step definition, not changing the actual code. Trick
    // PHP_CodeSniffer so it doesn't throw 'Useless method detected.'.
    // @codingStandardsIgnoreLine
    $tricksy = TRUE;
    parent::assertCheckboxChecked($radio);
  }

  /**
   * {@inheritdoc}
   */
  public function assertPageNotContainsText($text) {
    // When running in a JS enabled browser, check that the text is not visually
    // visible.
    if ($this->browserSupportsJavaScript()) {
      $xpath = '//*[text()[contains(.,"' . $text . '")]]';
      foreach ($this->getSession()->getPage()->findAll('xpath', $xpath) as $element) {
        if ($element->isVisible()) {
          throw new ExpectationFailedException("Element with text '$text' is visually visible.");
        }
      }
    }
    // Default to the standard behavior of checking that the response body
    // doesn't contain the text.
    else {
      parent::assertPageNotContainsText($text);
    }
  }

  /**
   * {@inheritdoc}
   *
   * The parent method already waits for animations to finish. We want to add
   * a friendlier name to explain in the tests that we are waiting for
   * animations and not for AJAX operations in that step.
   *
   * @Given I wait for animations to finish
   */
  public function iWaitForAjaxToFinish($event = NULL) {
    // We're just adding a step definition, not changing the actual code. Trick
    // PHP_CodeSniffer so it doesn't throw 'Useless method detected.'.
    // @codingStandardsIgnoreLine
    $tricksy = TRUE;
    parent::iWaitForAjaxToFinish($event);
  }

  /**
   * {@inheritdoc}
   *
   * Overrides the parent method in order to support Select2.
   */
  public function selectOption($select, $option): void {
    if ($field = $this->select2IsUsed($select, $option)) {
      $this->selectSelect2Option($field, $option);
      return;
    }
    parent::selectOption($select, $option);
  }

  /**
   * {@inheritdoc}
   *
   * Overrides the parent method in order to support Select2.
   */
  public function additionallySelectOption($select, $option): void {
    if ($field = $this->select2IsUsed($select, $option)) {
      $this->selectSelect2Option($field, $option);
      return;
    }
    parent::additionallySelectOption($select, $option);
  }

  /**
   * Checks if a given select field is using Select2.
   *
   * @param string $select
   *   The select.
   * @param string $option
   *   The option to be selected.
   *
   * @return \Behat\Mink\Element\NodeElement|false
   *   It returns the field as node element, if Select2 is used it or FALSE
   *   otherwise.
   */
  protected function select2IsUsed(string $select, string $option) {
    // In non-Javascript browsers Select2 nicely degrades to a simple select.
    if (!$this->browserSupportsJavaScript()) {
      return FALSE;
    }

    $field = $this->getSession()->getPage()->findField($select);
    if (!$field->getParent()->find('xpath', '//select[contains(@class, "select2-widget")]')) {
      // This not a Select2 widget but a simple select.
      return FALSE;
    }
    return $field;
  }

  /**
   * Selects a Select2 option.
   *
   * @param \Behat\Mink\Element\NodeElement $field
   *   The select field as a node element.
   * @param string $option
   *   The option to be selected.
   */
  protected function selectSelect2Option(NodeElement $field, string $option): void {
    $select2_search = $field->getParent()->find('xpath', '//select[contains(@class, "select2-widget")]/following-sibling::span//input[@class="select2-search__field"]');
    $select2_search->setValue($option);
    $select2_option = $this->getSession()->getPage()->find('xpath', '//li[contains(text(), "' . $option . '")]');
    $select2_option->click();
  }

  /**
   * Deselects a Select2 option by clicking the x button in the chip.
   *
   * @param string $option
   *   The option to be deselected.
   * @param string $select
   *   The select field name.
   *
   * @throws \Exception
   *   Thrown if the field is not a select2 widget.
   *
   * @Given I deselect the option :option from the :select select2 widget
   */
  public function deselectSelect2Option(string $option, string $select): void {
    if (!$field = $this->select2IsUsed($select, $option)) {
      throw new \Exception('This method can only be used for a select2 widget.');
    }

    $xpath = '//li[contains(@class, "select2-selection__choice") and contains(text(), "' . $option . '")]/span[contains(@class, "select2-selection__choice__remove")]';
    if (!$selected_option_remove = $field->getParent()->find('xpath', $xpath)) {
      throw new \Exception("The {$option} was not found as selected.");
    }
    $selected_option_remove->click();
  }

}

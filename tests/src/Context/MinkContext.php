<?php

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\MinkContext as DrupalExtensionMinkContext;
use Drupal\joinup\Traits\MaterialDesignTrait;

/**
 * Provides step definitions for interacting with Mink.
 */
class MinkContext extends DrupalExtensionMinkContext {

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
    $tricksy = TRUE;
    parent::assertCheckboxChecked($radio);
  }

  /**
   * {@inheritdoc}
   */
  public function assertPageNotContainsText($text) {
    // When running in a JS enabled browser, check that the text is not visually
    // visible.
    if ($this->browserSupportsJavascript()) {
      $xpath = '//*[text()[contains(.,"' . $text . '")]]';
      foreach ($this->getSession()->getPage()->findAll('xpath', $xpath) as $element) {
        if ($element->isVisible()) {
          throw new \PHPUnit_Framework_ExpectationFailedException("Element with text '$text' is visually visible.");
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
    $tricksy = TRUE;
    parent::iWaitForAjaxToFinish($event);
  }

}

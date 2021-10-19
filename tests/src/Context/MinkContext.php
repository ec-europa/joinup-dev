<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

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
   * Waits to make sure that the page is loaded completely.
   *
   * @param int $sec
   *   The seconds to wait.
   *
   * @Given I wait :sec seconds until the page is loaded( completely)
   */
  public function iWaitTillDocumentIsReady(int $sec): void {
    $this->getSession()->wait($sec * 1000, "document.readyState === 'complete'");
  }

}

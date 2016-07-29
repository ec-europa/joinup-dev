<?php

/**
 * @file
 * Contains \FeatureContext.
 */

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Defines generic step definitions.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

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
   * Click on the field label.
   *
   * @param string $label
   *   The label text to find in the page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   Thrown when the element with the given label is not found.
   *
   * @When I click the label :label
   */
  public function clickLabel($label) {
    $node = $this->getSession()->getPage()->find('named', array('content', $label));

    if (!is_object($node)) {
      throw new ElementNotFoundException('Node with text ' . $label . " not found in page.");
    }

    $node->click();
  }

  /**
   * Find the selected option of the select and check the text.
   *
   * @param string $option
   *   Text value of the option to find.
   * @param string $select
   *   Css selector of the select field.
   *
   * @throws \Exception
   *
   * @Then the option with text :option from select :select is selected
   */
  public function theOptionWithTextFromSelectIsSelected($option, $select) {
    $selectField = $this->getSession()->getPage()->find('css', $select);
    if ($selectField === NULL) {
      throw new \Exception(sprintf(
        'The select "%s" was not found in the page %s',
        $select, $this->getSession()->getCurrentUrl())
      );
    }

    $optionField = $selectField->find('xpath', '//option[@selected="selected"]');
    if ($optionField === NULL) {
      throw new \Exception(sprintf(
        'No option is selected in the %s select in the page %s',
        $select, $this->getSession()->getCurrentUrl())
      );
    }

    if ($optionField->getHtml() != $option) {
      throw new \Exception(sprintf(
        'The option "%s" was not selected in the page %s, %s was selected',
        $option,
        $this->getSession()->getCurrentUrl(),
        $optionField->getHtml())
      );
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
  public function theOptionWithTextFromSelectIsNotSelected($option, $select) {
    $selectField = $this->getSession()->getPage()->find('css', $select);
    if ($selectField === NULL) {
      throw new \Exception(sprintf(
          'The select "%s" was not found in the page %s',
          $select, $this->getSession()->getCurrentUrl())
      );
    }

    $optionField = $selectField->find('xpath', '//option[@selected="selected"]');
    if ($optionField !== NULL) {
      if ($optionField->getHtml() == $option) {
        throw new \Exception(sprintf(
            'The option "%s" was selected in the page %s',
            $option,
            $this->getSession()->getCurrentUrl()
        ));
      }
    }
  }

}

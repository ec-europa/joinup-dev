<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use PHPUnit\Framework\Assert;

/**
 * Helper methods for dealing with forms.
 */
trait FormTrait {

  /**
   * Asserts that the given form submission buttons are present on the page.
   *
   * @param array $labels
   *   An array containing the button labels that are expected to be present.
   * @param bool $strict
   *   If set to TRUE, an exception will be thrown if there are any unexpected
   *   for submission buttons on the page.
   *
   * @throws \Exception
   *   Thrown when an expected button is not present or when an unexpected
   *   button is present.
   */
  public function assertSubmitButtonsVisible(array $labels, bool $strict = TRUE) {
    $page = $this->getSession()->getPage();
    $not_found = [];
    foreach ($labels as $label) {
      if (!$page->findButton($label)) {
        $not_found[] = $label;
      }
    }

    if (!empty($not_found)) {
      throw new \Exception('Button(s) expected, but not found: ' . implode(', ', $not_found));
    }

    if ($strict) {
      // Only check the actual form submit buttons, ignore other buttons that
      // might be present in wysiwygs or are used to add multiple values to a
      // field.
      $expected_count = count($labels);
      $actual_count = count($this->getSession()->getPage()->findAll('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " form-actions ")]//input[@type = "submit"]'));
      Assert::assertEquals($expected_count, $actual_count);
    }
  }

}

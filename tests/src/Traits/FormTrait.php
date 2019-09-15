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
   *   form submission buttons on the page, or if the buttons are in the wrong
   *   order.
   *
   * @throws \Exception
   *   Thrown when an expected button is not present or when an unexpected
   *   button is present.
   */
  public function assertSubmitButtonsVisible(array $labels, bool $strict = TRUE) {
    $buttons = $this->getSession()->getPage()->findAll('xpath', '//div[contains(concat(" ", normalize-space(@class), " "), " form-actions ")]//input[@type = "submit"]');
    $actual_labels = [];
    foreach ($buttons as $button) {
      $actual_labels[] = $button->getValue();
    }

    $missing_labels = array_diff($labels, $actual_labels);

    if (!empty($missing_labels)) {
      throw new \Exception('Button(s) expected, but not found: ' . implode(', ', $missing_labels));
    }

    if ($strict) {
      Assert::assertSame($actual_labels, $labels);
    }
  }

}

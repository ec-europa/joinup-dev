<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use PHPUnit\Framework\Assert;

/**
 * Contains utility methods.
 */
trait UtilityTrait {

  /**
   * Explodes and sanitizes a comma separated step argument.
   *
   * @param string $argument
   *   The string argument.
   *
   * @return array
   *   The argument as array, with trimmed non-empty values.
   */
  protected function explodeCommaSeparatedStepArgument(string $argument): array {
    $argument = explode(',', $argument);
    $argument = array_map('trim', $argument);
    $argument = array_filter($argument);

    return $argument;
  }

  /**
   * Checks if an element is visible for human eyes.
   *
   * To enable certain elements to be visible for screen readers but not for
   * human eyes, Drupal provides the 'visually-hidden' class. This uses a trick
   * that fools the browser in thinking this element is still visible, even
   * though it doesn't actually show up visually in the page.
   *
   * This method will check both that the browser reports this element to be
   * visible, and that the 'visually-hidden' class is absent.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to check for visibility.
   *
   * @throws \Exception
   *   Thrown if the element is not visible.
   */
  protected function assertVisuallyVisible(NodeElement $element): void {
    Assert::assertTrue($this->isVisuallyVisible($element), 'The element is visually visible');
  }

  /**
   * Checks if an element is invisible for human eyes.
   *
   * To enable certain elements to be visible for screen readers but not for
   * human eyes, Drupal provides the 'visually-hidden' class. This uses a trick
   * that fools the browser in thinking this element is still visible, even
   * though it doesn't actually show up visually in the page.
   *
   * This method will check first that the browser reports this element to be
   * invisible; if that doesn't work, then it checks if the 'visually-hidden'
   * class is present.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to check for visibility.
   *
   * @throws \Exception
   *   Thrown if the element is visible.
   */
  protected function assertNotVisuallyVisible(NodeElement $element): void {
    Assert::assertFalse($this->isVisuallyVisible($element), 'The element is not visually visible');
  }

  /**
   * Returns if an element is visible for human eyes.
   *
   * To enable certain elements to be visible for screen readers but not for
   * human eyes, Drupal provides the 'visually-hidden' class. This uses a trick
   * that fools the browser in thinking this element is still visible, even
   * though it doesn't actually show up visually in the page.
   *
   * This method will check both that the browser reports this element to be
   * visible, and that the 'visually-hidden' class is absent.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to check.
   *
   * @return bool
   *   True if human optical receptors will be able to detect this particular
   *   element.
   *
   * @throws \Exception
   *   Thrown if the browser does not support JavaScript or if the passed in
   *   element is no longer present on the page.
   */
  protected function isVisuallyVisible(NodeElement $element): bool {
    \assert(method_exists($this, 'assertJavaScriptEnabledBrowser'), __METHOD__ . ' depends on BrowserCapabilityDetectionTrait. Please include it in your class.');
    // This only works on JS-enabled browsers.
    $this->assertJavaScriptEnabledBrowser();

    /** @var \Behat\Mink\Driver\Selenium2Driver $driver */
    $driver = $this->getMink()->getSession()->getDriver();

    // First check if the browser reports this to be visible.
    $is_visible = $driver->isVisible($element->getXpath());

    // The "visually-hidden" class is used in Drupal to hide elements that
    // should still be visible in screen readers. This tricks the browser in
    // thinking the element is actually visible even though human eyes won't
    // find it. This class uses the "clip" css property, that will cut down the
    // visible portion of the element. This css property works only when the
    // "position" property is set either to fixed or absolute.
    $webdriver_element = $driver->getWebDriverSession()->element('xpath', $element->getXpath());
    $is_clipped = in_array($webdriver_element->css('position'), ['fixed', 'absolute']);

    return $is_visible && !$is_clipped;
  }

  /**
   * Converts property values of an object to the ones defined in a mapping.
   *
   * Useful to convert human-readable strings used in tests to machine-readable
   * ones.
   *
   * @param object $object
   *   The object itself.
   * @param string $property
   *   The source property name. It will be used also as destination if the
   *   related parameter is not passed.
   * @param array $mapping
   *   An array of mapped values, where keys are the human-readable strings.
   * @param string|null $destination
   *   The destination property name. If left empty, the source property will
   *   be reused. When specified, the source property gets unset from the
   *   object.
   */
  protected static function convertObjectPropertyValues($object, string $property, array $mapping, ?string $destination = NULL): void {
    if (!property_exists($object, $property)) {
      return;
    }

    // Force the use of human readable values in Behat test scenarios, throw
    // an exception if the numeric values are used.
    if (!array_key_exists($object->$property, $mapping)) {
      $supported_values = implode(', ', array_keys($mapping));
      throw new \UnexpectedValueException("Unexpected value for {$property} '{$object->$property}'. Supported values are: $supported_values.");
    }

    // If no destination property is specified, reuse the source property.
    $destination = $destination ?: $property;

    // Replace the human readable value with the expected boolean.
    $object->$destination = $mapping[$object->$property];

    // When a destination property has been specified, delete the source
    // property.
    if ($destination !== $property) {
      unset($object->$property);
    }
  }

  /**
   * Executes a callback until it succeeds or until timeout is hit.
   *
   * @param callable $callback
   *   The callback to execute until it returns a truthy value or timeout.
   * @param int $timeout
   *   The maximum wait time. Defaults to 5 seconds.
   *
   * @return mixed
   *   The result of the last invocation of the callback.
   */
  protected function waitUntil(callable $callback, int $timeout = 5) {
    $end = microtime(TRUE) + $timeout;
    do {
      usleep(100000);
      $result = $callback();
    } while (microtime(TRUE) < $end && !$result);

    return $result;
  }

  /**
   * Converts an ordinal number (1st, 2nd, 17th etc) to a normal number.
   *
   * @param string $number
   *   The ordinal number.
   *
   * @return int
   *   The number.
   *
   * @throws \Exception
   *   Thrown if the number is not an ordinal.
   */
  protected function convertOrdinalToNumber(string $number): int {
    $return = preg_replace('/\\b(\d+)(?:st|nd|rd|th)\\b/', '$1', $number);
    if (!is_numeric($return)) {
      throw new \Exception("Could not convert {$number} to a number.");
    }
    return (int) $return;
  }

}

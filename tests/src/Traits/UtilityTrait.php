<?php

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\UnsupportedDriverActionException;

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
  protected function explodeCommaSeparatedStepArgument($argument) {
    $argument = explode(',', $argument);
    $argument = array_map('trim', $argument);
    $argument = array_filter($argument);

    return $argument;
  }

  /**
   * Checks that we are running on a JavaScript-enabled browser.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   Thrown when not running on a JS-enabled browser.
   */
  protected function assertJavaScriptEnabledBrowser() {
    $driver = $this->getMink()->getSession()->getDriver();
    try {
      $driver->isVisible('//body');
    }
    catch (UnsupportedDriverActionException $e) {
      // Show a helpful error message.
      throw new UnsupportedDriverActionException('This test needs to run on a real browser like Selenium or PhantomJS. Please add the "@javascript" tag to the scenario.', $driver);
    }
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
   */
  protected function assertVisuallyVisible(NodeElement $element) {
    \PHPUnit_Framework_Assert::assertTrue($this->isVisuallyVisible($element), 'The element is visually visible');
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
   */
  protected function assertNotVisuallyVisible(NodeElement $element) {
    \PHPUnit_Framework_Assert::assertFalse($this->isVisuallyVisible($element), 'The element is not visually visible');
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
   * @return bool
   *   True if human optical receptors will be able to detect this particular
   *   element.
   */
  protected function isVisuallyVisible(NodeElement $element) {
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
  protected static function convertObjectPropertyValues($object, $property, array $mapping, $destination = NULL) {
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
  protected function waitUntil(callable $callback, $timeout = 5) {
    $end = microtime(TRUE) + $timeout;
    do {
      usleep(100000);
      $result = $callback();
    } while (microtime(TRUE) < $end && !$result);

    return $result;
  }

}

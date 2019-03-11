<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

use Behat\Mink\Element\NodeElement;
use WebDriver\Exception\StaleElementReference;

/**
 * Contains helper methods for interacting with the keyboard.
 */
trait KeyboardInteractionTrait {

  /**
   * Presses a key in a given element.
   *
   * Works only in Javascript-enabled browsers.
   *
   * @param string $key
   *   The human readable name of the key to press.
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element in which to press the key.
   *
   * @throws \Exception
   *   Thrown when the browser doesn't support Javascript or when the field is
   *   not found.
   */
  protected function pressKeyInElement(string $key, NodeElement $element): void {
    // Translate the human readable key name into an escape character code.
    $key = $this->translateKey($key);

    $element->keyDown($key);
    try {
      $element->keyUp($key);
    }
    catch (StaleElementReference $e) {
      // Depending on the implementation of the user interaction we are trying
      // to trigger by pressing the enter key, it is posssible the action is
      // triggered on the keydown event or on the keyup event. If it is
      // implemented on the keydown event then it might happen that the page
      // refreshes immediately without waiting for the key to be released.
      // Ignore the fact that the field might already be gone when performing
      // the keyup event.
    }
  }

  /**
   * Translates a human readable name for a keyboard key into an escape code.
   *
   * @param string $key_name
   *   The human readable key name.
   *
   * @return string|int
   *   The escape code for the key, or the originally passed string if the key
   *   name is not defined in the mapping.
   */
  protected function translateKey(string $key_name) {
    $keyboard_escape_codes = [
      'alt' => 18,
      'backspace' => 8,
      'break' => 19,
      'capslock' => 20,
      'ctrl' => 17,
      'delete' => 46,
      'down' => 40,
      'end' => 35,
      'enter' => 13,
      'esc' =>  27,
      'escape' =>  27,
      'home' =>  36,
      'insert' => 45,
      'left' => 37,
      'pagedown' => 34,
      'pageup' => 33,
      'pause' => 19,
      'right' => 39,
      'shift' => 16,
      'tab' => 9,
      'up' => 38,
    ];
    // Support for all variations, e.g. ESC, Esc, page up, PageUp.
    if (strlen($key_name) > 1) {
      $normalized_key_name = strtolower(str_replace(' ', '', $key_name));
      if (array_key_exists($normalized_key_name, $keyboard_escape_codes)) {
        return $keyboard_escape_codes[$normalized_key_name];
      }
    }
    return $key_name;
  }

}

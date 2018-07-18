<?php

declare(strict_types = 1);

namespace Drupal\joinup;

/**
 * Class containing all the key codes for the Javascript KeyboardEvent event.
 *
 * When using methods like \Behat\Mink\Driver\DriverInterface::keyDown(), the
 * Javascript key codes needs to be sent.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/KeyboardEvent
 */
final class KeyboardEventKeyCodes {

  const LEFT_ARROW = 37;
  const RIGHT_ARROW = 39;
  const UP_ARROW = 38;
  const DOWN_ARROW = 40;

}

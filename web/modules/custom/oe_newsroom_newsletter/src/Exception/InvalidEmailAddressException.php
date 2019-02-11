<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\Exception;

/**
 * Exception thrown when the passed in e-mail address is invalid.
 */
class InvalidEmailAddressException extends \InvalidArgumentException {
}

<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter\Exception;

/**
 * Exception thrown when a passed in e-mail address is already subscribed.
 */
class EmailAddressAlreadySubscribedException extends \InvalidArgumentException {
}

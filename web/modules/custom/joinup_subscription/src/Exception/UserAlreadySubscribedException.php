<?php

namespace Drupal\joinup_subscription\Exception;

/**
 * Exception thrown when a user is being resubscribed.
 *
 * This is a checked exception that should be handled on the calling side and
 * converted to an appropriate response, like a helpful message shown to the
 * user, a log entry, or a RuntimeException.
 */
class UserAlreadySubscribedException extends \LogicException {
}

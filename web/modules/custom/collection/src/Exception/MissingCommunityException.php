<?php

declare(strict_types = 1);

namespace Drupal\collection\Exception;

/**
 * Exception thrown when the required reference to a community is missing.
 *
 * @see \Drupal\collection\Entity\CommunitiesContentInterface::getCommunity()
 */
class MissingCommunityException extends \Exception {

}

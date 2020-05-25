<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Exception;

/**
 * Exception thrown when the required reference to a group is missing.
 *
 * @see \Drupal\joinup_group\Entity\GroupContentInterface::getGroup()
 */
class MissingGroupException extends \Exception {

}

<?php

declare(strict_types = 1);

namespace Drupal\collection\Exception;

/**
 * Exception thrown when the required reference to a collection is missing.
 *
 * @see \Drupal\collection\Entity\CollectionContentInterface::getCollection()
 */
class MissingCollectionException extends \Exception {

}

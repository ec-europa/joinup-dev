<?php

declare(strict_types = 1);

namespace Drupal\solution\Exception;

/**
 * Exception thrown when the required reference to a solution is missing.
 *
 * @see \Drupal\solution\Entity\SolutionContentInterface::getSolution()
 */
class MissingSolutionException extends \Exception {

}

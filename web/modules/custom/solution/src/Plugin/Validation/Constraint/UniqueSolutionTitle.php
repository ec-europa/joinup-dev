<?php

declare(strict_types = 1);

namespace Drupal\solution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a solution's title is unique within the collections it belongs to.
 *
 * @Constraint(
 *   id = "UniqueSolutionTitle",
 *   label = @Translation("Unique solution title within collections", context = "Validation"),
 * )
 */
class UniqueSolutionTitle extends Constraint {

  /**
   * The message to show when validation fails.
   *
   * @var string
   */
  public $message = 'A solution titled %value already exists in this collection. Please choose a different title.';

}

<?php

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
class UniqueSolutionTitleConstraint extends Constraint {

  public $message = 'A solution titled %value already exists in this collection. Please choose a different title.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\solution\Plugin\Validation\Constraint\UniqueSolutionTitleValidator';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions() {
    return [];
  }

}

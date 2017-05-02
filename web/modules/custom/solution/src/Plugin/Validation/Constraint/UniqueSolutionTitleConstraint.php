<?php

namespace Drupal\solution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a solution's title is unique within the solutions.
 *
 * This constraint takes into account that releases can have same title as the
 * original entity.
 *
 * @Constraint(
 *   id = "UniqueSolutionTitle",
 *   label = @Translation("Unique title within a solution constraint", context = "Validation"),
 * )
 */
class UniqueSolutionTitleConstraint extends Constraint {

  public $message = 'Content with @field_name %value already exists.';

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

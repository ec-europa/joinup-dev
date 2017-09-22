<?php

namespace Drupal\asset_release\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a release's title and version is unique within its solution.
 *
 * @Constraint(
 *   id = "UniqueAssetReleaseTitleVersion",
 *   label = @Translation("Unique release title and version within solution", context = "Validation"),
 * )
 */
class UniqueAssetReleaseTitleVersionConstraint extends Constraint {

  /**
   * The message to show when the constraint is not met.
   *
   * @var string
   */
  public $message = 'A release with title %title and version %version already exists in this solution. Please choose a different title or version.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\asset_release\Plugin\Validation\Constraint\UniqueAssetReleaseTitleVersionValidator';
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

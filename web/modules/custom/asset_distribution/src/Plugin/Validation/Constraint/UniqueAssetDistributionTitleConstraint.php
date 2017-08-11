<?php

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a distribution's title is unique within its release.
 *
 * @Constraint(
 *   id = "UniqueAssetDistributionTitle",
 *   label = @Translation("Unique distribution title within release", context = "Validation"),
 * )
 */
class UniqueAssetDistributionTitleConstraint extends Constraint {

  /**
   * The message to show when the constraint is not met.
   *
   * @var string
   */
  public $message = 'A distribution with title %title already exists in this %bundle. Please choose a different title.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\asset_distribution\Plugin\Validation\Constraint\UniqueAssetDistributionTitleValidator';
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

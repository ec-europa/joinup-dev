<?php

namespace Drupal\asset_release\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a asset_release's title is unique within the asset_releases.
 *
 * This constraint takes into account that releases can have same title as the
 * original entity.
 *
 * @Constraint(
 *   id = "UniqueAssetReleaseTitle",
 *   label = @Translation("Unique title within a asset_release constraint", context = "Validation"),
 * )
 */
class UniqueAssetReleaseTitleConstraint extends Constraint {

  public $message = 'Content with @field_name %value already exists.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\asset_release\Plugin\Validation\Constraint\UniqueAssetReleaseTitleValidator';
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

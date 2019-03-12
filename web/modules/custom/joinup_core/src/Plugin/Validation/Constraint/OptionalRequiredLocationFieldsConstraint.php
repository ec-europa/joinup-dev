<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if one of the given fields are empty or not.
 *
 * @Constraint(
 *   id = "OptionalRequiredLocationFields",
 *   label = @Translation("Optional required location fields constraint", context = "Validation"),
 * )
 */
class OptionalRequiredLocationFieldsConstraint extends Constraint {

  public $message = 'At least one location field should be filled in.';

  /**
   * {@inheritdoc}
   */
  public function validatedBy() {
    return '\Drupal\joinup_core\Plugin\Validation\Constraint\OptionalRequiredLocationFieldsValidator';
  }

  /**
   * The first field which needs to be checked.
   *
   * @var string
   */
  public $field_name_1;

  /**
   * The second field which needs to be checked.
   *
   * @var string
   */
  public $field_name_2;

}

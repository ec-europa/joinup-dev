<?php

namespace Drupal\joinup_event\Plugin\Validation\Constraint;

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

}

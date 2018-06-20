<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an entity field has unique values.
 *
 * @Constraint(
 *   id = "UniqueFieldValue",
 *   label = @Translation("Unique values in a field constraint", context = "Validation"),
 * )
 */
class UniqueFieldValueConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The value %value is already selected for field %field_name.';

}

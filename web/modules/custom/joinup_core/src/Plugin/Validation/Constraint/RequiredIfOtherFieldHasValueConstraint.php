<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Makes a field required if another field has a certain value.
 *
 * @Constraint(
 *   id = "RequiredIfOtherFieldHasValue",
 *   label = @Translation("Required if other field has value", context = "Validation"),
 * )
 */
class RequiredIfOtherFieldHasValueConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The field %field is required when %dependent_field is set to %dependent_value.';

  /**
   * The violation message to show when the dependent field has multiple values.
   *
   * @var string
   */
  public $multipleValuesMessage = 'The field %field is required when %dependent_field is set to any of the values %dependent_value.';

  /**
   * The field name to check for values.
   *
   * @var string
   */
  public $field;

  /**
   * The list of values that should be set to trigger the required state.
   *
   * @var array
   */
  public $values;

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['field', 'values'];
  }

}

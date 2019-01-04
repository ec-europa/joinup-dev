<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Drupal\joinup_core\Traits\FieldItemsTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the "Required if other field has value" constraint.
 */
class RequiredIfOtherFieldHasValueConstraintValidator extends ConstraintValidator {

  use FieldItemsTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    // If the field has a value, bail out.
    if (!$items->isEmpty()) {
      return;
    }

    $entity = $items->getEntity();
    /** @var \Drupal\joinup_core\Plugin\Validation\Constraint\RequiredIfOtherFieldHasValueConstraint $constraint */
    /** @var \Drupal\Core\Field\FieldItemListInterface $dependent_field */
    $dependent_field = $entity->get($constraint->field);

    // If the dependent field is empty too, then the current field is not
    // required.
    if ($dependent_field->isEmpty()) {
      return;
    }

    $dependent_values = $this->getFieldItemListMainPropertyValues($dependent_field);
    $matched_values = array_intersect($dependent_values, $constraint->values);
    if (!$matched_values) {
      return;
    }

    $labels = [];
    foreach (array_keys($matched_values) as $delta) {
      $labels[] = $this->getFieldItemDisplayValue($dependent_field->get($delta));
    }

    $message = count($labels) > 1 ? $constraint->multipleValuesMessage : $constraint->message;
    $this->context->addViolation($message, [
      '%field' => $items->getFieldDefinition()->getLabel(),
      '%dependent_field' => $dependent_field->getFieldDefinition()->getLabel(),
      '%dependent_value' => implode(', ', $labels),
    ]);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Drupal\joinup_core\Traits\FieldItemDisplayValueTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the "Required if other field has value" constraint.
 */
class RequiredIfOtherFieldHasValueConstraintValidator extends ConstraintValidator {

  use FieldItemDisplayValueTrait;

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

    $values = [];
    foreach ($dependent_field as $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $values[] = $item->get($item::mainPropertyName())->getValue();
    }

    if (array_intersect($constraint->values, $values)) {
      $labels = array_map(function ($item) {
        return $this->getFieldItemDisplayValue($item);
      }, iterator_to_array($dependent_field));

      $this->context->addViolation($constraint->message, [
        '%field' => $items->getFieldDefinition()->getLabel(),
        '%dependent_field' => $dependent_field->getFieldDefinition()->getLabel(),
        '%dependent_value' => implode(', ', $labels),
      ]);
    }
  }

}

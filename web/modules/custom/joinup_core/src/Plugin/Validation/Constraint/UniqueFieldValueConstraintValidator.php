<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Drupal\joinup_core\Traits\FieldItemsTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field has unique values.
 */
class UniqueFieldValueConstraintValidator extends ConstraintValidator {

  use FieldItemsTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$items->count()) {
      return;
    }

    $seen = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $value = $item->{$item::mainPropertyName()};

      // @todo Add support for complex field types if needed.
      if (!is_scalar($value)) {
        continue;
      }

      if (isset($seen[$value])) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%field_name', $items->getFieldDefinition()->getLabel())
          ->setParameter('%value', $this->getFieldItemDisplayValue($item))
          ->atPath((string) $delta)
          ->addViolation();
      }

      $seen[$value] = TRUE;
    }
  }

}

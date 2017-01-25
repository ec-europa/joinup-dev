<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field has unique values.
 */
class UniqueFieldValueConstraintValidator extends ConstraintValidator {

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
          ->setParameter('%value', $this->getFormattedValue($item, $value))
          ->atPath((string) $delta)
          ->addViolation();
      }

      $seen[$value] = TRUE;
    }
  }

  /**
   * Formats the item value to be used in the violation message.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The entity field item.
   * @param mixed $value
   *   The main value of the field item.
   *
   * @return string
   *   The value to be used in the violation message.
   */
  protected function getFormattedValue(FieldItemInterface $item, $value) {
    if ($item instanceof EntityReferenceItem && !empty($item->entity)) {
      return $item->entity->label();
    }

    return (string) $value;
  }

}

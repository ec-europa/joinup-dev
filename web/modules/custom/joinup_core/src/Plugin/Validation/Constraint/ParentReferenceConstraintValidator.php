<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a reference field is not pointing to a parent of the entity.
 */
class ParentReferenceConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$items->count()) {
      return;
    }

    /** @var ParentReferenceConstraint $constraint */
    $service = $constraint->service;
    $method = $constraint->method;

    $service = \Drupal::service($service);
    $parent = call_user_func([$service, $method], $items->getEntity());

    if (empty($parent)) {
      return;
    }

    foreach ($items as $delta => $item) {
      if ($item->target_id === $parent->id()) {
        $this->context->buildViolation($constraint->message)
          ->setParameter('%field_name', $items->getFieldDefinition()->getLabel())
          ->setParameter('%parent', $item->entity->label())
          ->atPath((string) $delta)
          ->addViolation();
      }
    }
  }

}

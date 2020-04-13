<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Location fields.
 */
class EventLocationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if ($entity->bundle() !== 'event') {
      return;
    }

    if ($entity->get('field_location')->isEmpty() && $entity->get('field_event_online_location')->isEmpty()) {
      $this->context->addViolation($constraint->message);
    }
  }

}

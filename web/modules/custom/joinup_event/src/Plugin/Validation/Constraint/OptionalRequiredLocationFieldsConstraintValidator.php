<?php

namespace Drupal\joinup_event\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Location fields.
 */
class OptionalRequiredLocationFieldsConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */

    if ($entity->bundle() !== 'event') {
      // Don't do anything if it is not an event content.
      return;
    }

    // Add Violation when both of them are empty.
    if ($entity->get('field_location')
        ->isEmpty() && $entity->get('field_event_online_location')
        ->isEmpty()) {
      $this->context->addViolation($constraint->message);
    }
  }

}

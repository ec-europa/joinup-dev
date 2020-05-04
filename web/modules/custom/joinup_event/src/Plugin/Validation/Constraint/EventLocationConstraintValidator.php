<?php

declare(strict_types = 1);

namespace Drupal\joinup_event\Plugin\Validation\Constraint;

use Drupal\joinup_event\Entity\EventInterface;
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
    if (!$entity instanceof EventInterface) {
      return;
    }

    if (empty($entity->getLocation()) && empty($entity->getOnlineLocation())) {
      $this->context->addViolation($constraint->message);
    }
  }

}

<?php

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a validator for 'DownloadEvent' constraint.
 */
class DownloadEventValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\asset_distribution\Entity\DownloadEvent $entity */
    if (isset($entity)) {
      if (!$entity->getOwner() && !$entity->get('mail')->value) {
        $this->context->addViolation($constraint->userOrMail);
      }
    }
  }

}

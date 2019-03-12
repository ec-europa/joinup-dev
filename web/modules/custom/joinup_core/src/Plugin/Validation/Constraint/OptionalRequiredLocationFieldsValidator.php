<?php

namespace Drupal\joinup_core\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use \Drupal\node\NodeInterface as Node;

/**
 * Validates the Location fields.
 */
class OptionalRequiredLocationFieldsValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (!$entity instanceof Node) {
      // We only want to work with nodes.
      return;
    }

    if (!$entity->bundle() === 'event') {
      // Don't do anything if it is not an event content.
      return;
    }

    // Retrieve field values from the Constraint.
    $field_name_1 = $constraint->fieldName1;
    $field_name_2 = $constraint->fieldName2;

    // Just to make sure the fields exist.
    if ($entity->hasField($field_name_1) && $entity->hasField($field_name_2)) {
      // Add Violation when both of them are empty.
      if ($entity->get($field_name_1)->isEmpty() && $entity->get($field_name_2)->isEmpty()) {
        $this->context->addViolation($constraint->message);
      }
    }
  }

}

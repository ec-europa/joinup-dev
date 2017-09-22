<?php

namespace Drupal\solution\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 *
 * This is the validator for the UniqueSolutionTitleConstraint.
 *
 * The following checks make sure that a solution must have a unique title among
 * within their collections.
 *
 * @see \Drupal\solution\Plugin\Validation\Constraint\UniqueSolutionTitleConstraint
 */
class UniqueSolutionTitleValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $items->getEntity();

    if (!solution_title_is_unique($entity)) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
        '@field_name' => Unicode::strtolower($items->getFieldDefinition()->getLabel()),
      ]);
    }
  }

}

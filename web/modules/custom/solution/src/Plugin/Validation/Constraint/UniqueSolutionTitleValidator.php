<?php

declare(strict_types = 1);

namespace Drupal\solution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 *
 * This is the validator for the UniqueSolutionTitle.
 *
 * The following checks make sure that a solution must have a unique title among
 * within their collections.
 *
 * @see \Drupal\solution\Plugin\Validation\Constraint\UniqueSolutionTitle
 */
class UniqueSolutionTitleValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $items->getEntity();

    if ($solution->bundle() !== 'solution') {
      throw new \InvalidArgumentException("This validator is designated only for solutions.");
    }

    if (!solution_title_is_unique($entity)) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $solution->getEntityType()->getLowercaseLabel(),
        '@field_name' => $items->getFieldDefinition()->getLabel(),
      ]);
    }
  }

}

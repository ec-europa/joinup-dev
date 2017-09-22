<?php

namespace Drupal\asset_release\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a release's title and version are unique within the solution.
 *
 * This is the validator for the UniqueAssetReleaseTitleVersionConstraint.
 *
 * The following checks make sure that a release must have a unique combination
 * of title and version within the solution.
 *
 * @see \Drupal\asset_release\Plugin\Validation\Constraint\UniqueAssetReleaseTitleVersionConstraint
 */
class UniqueAssetReleaseTitleVersionValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $items->getEntity();

    if (!asset_release_title_version_is_unique($entity)) {
      $this->context->addViolation($constraint->message, [
        '%title' => $entity->label(),
        '%version' => $entity->get('field_isr_release_number')->first()->getValue()['value'],
      ]);
    }
  }

}

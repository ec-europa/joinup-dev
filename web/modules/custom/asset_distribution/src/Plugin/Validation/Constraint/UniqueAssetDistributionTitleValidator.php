<?php

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a distribution's title is unique within the release.
 *
 * This is the validator for the UniqueAssetReleaseTitleConstraint.
 *
 * @see \Drupal\asset_distribution\Plugin\Validation\Constraint\UniqueAssetReleaseTitleConstraint
 */
class UniqueAssetDistributionTitleValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $items->getEntity();

    /** @var \Drupal\rdf_entity\RdfInterface $rdf_entity */
    $rdf_entity = \Drupal::routeMatch()->getParameter('rdf_entity');
    // If the entity was not created through the normal route, return.
    if (empty($rdf_entity)) {
      return;
    }
    $field_name = $rdf_entity->bundle() === 'solution' ? 'field_is_distribution' : 'field_isr_distribution';
    /** @var \Drupal\rdf_entity\RdfInterface[] $distributions */
    $distributions = $rdf_entity->get($field_name)->referencedEntities();
    foreach ($distributions as $distribution) {
      if ($distribution->label() === $entity->label() && $distribution->id() !== $entity->id()) {
        $this->context->addViolation($constraint->message, [
          '%title' => $entity->label(),
          '%bundle' => strtolower($rdf_entity->get('rid')->entity->label()),
        ]);

        return;
      }
    }
  }

}

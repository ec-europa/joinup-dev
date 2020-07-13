<?php

declare(strict_types = 1);

namespace Drupal\joinup_validation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 */
class UniqueFieldValueInBundleValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    $bundles = $constraint->bundles;
    if (!$item = $items->first()) {
      return;
    }
    $field_name = $items->getFieldDefinition()->getName();
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $items->getEntity();
    $bundle = $entity->bundle();
    if (!in_array($bundle, $bundles)) {
      // The constraint does not apply to this bundle.
      return;
    }

    $bundle_key = $entity->getEntityType()->getKey('bundle');
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');
    $main_property = $items->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName();

    $query = \Drupal::entityQuery($entity_type_id)
      ->condition($field_name, $item->{$main_property})
      ->condition($bundle_key, $bundles, 'IN');
    if (!empty($entity->id())) {
      $query->condition($id_key, $items->getEntity()->id(), '<>');
    }
    $value_taken = (bool) $query
      ->range(0, 1)
      ->count()
      ->execute();

    if ($value_taken) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->{$main_property},
        '@entity_type' => $entity->getEntityType()->getSingularLabel(),
        '@field_name' => mb_strtolower($items->getFieldDefinition()->getLabel()),
      ]);
    }
  }

}

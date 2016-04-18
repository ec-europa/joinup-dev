<?php

namespace Drupal\solution\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 */
class UniqueSolutionTitleValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }
    $field_name = $items->getFieldDefinition()->getName();
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $items->getEntity();

    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');

    // If the is_version_of field is not empty, check for the same title.
    if (!empty($entity->get('field_is_is_version_of')
      ->getValue()[0]['target_id'])
    ) {
      /** @var \Drupal\rdf_entity\RdfInterface $parent */
      $parent = Rdf::load($entity->get('field_is_is_version_of')
        ->getValue()[0]['target_id']);

      if ($parent->label() == $entity->label()) {
        // The release has the same name as the solution.
        return;
      }

      // Check if the name is the same to other releases of the entity.
      foreach ($parent->get('field_is_has_version')->getValue() as $release) {
        $simbling = Rdf::load($release['target_id']);
        if ($entity->label() == $simbling->label()) {
          return;
        }
      }
    }

    $query = \Drupal::entityQuery($entity_type_id)
      // The id could be NULL, so we cast it to 0 in that case.
      ->condition($id_key, (int) $items->getEntity()->id(), '<>')
      ->condition($field_name, $item->value)
      ->condition('rid', 'solution');
    // @todo: Discuss about it whether we need it.
    if (empty($entity->get('field_is_is_version_of')
      ->getValue()[0]['target_id'])
    ) {
      $query->notExists('field_is_is_version_of');
    }

    $value_taken = (bool) $query->range(0, 1)
      ->count()
      ->execute();
    if ($value_taken) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
        '@field_name' => Unicode::strtolower($items->getFieldDefinition()
          ->getLabel()),
      ]);
    }
  }

}

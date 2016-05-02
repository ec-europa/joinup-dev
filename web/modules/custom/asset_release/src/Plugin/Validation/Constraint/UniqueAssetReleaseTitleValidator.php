<?php

namespace Drupal\asset_release\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 *
 * This is the validator for the UniqueAssetReleaseInTitle constraint.
 * The asset_releases and the releases are actually the same entity. A asset_release
 * can have many releases and a release belongs to one asset_release. A release
 * cannot have releases or multiple asset_releases.
 *
 * The asset_release entity is defined by having an empty field is_version_of.
 * This is enough because releases can only be created through the asset_release and
 * automatically have the is_version_of field filled. An entity that has the
 * field is_version_of filled is automatically a release.
 *
 * The following checks make sure that a asset_release must have a unique title among
 * asset_releases and a release must have a unique title against other asset_releases and
 * their releases but can have the same name as their parent asset_release or their
 * sibling releases.
 */
class UniqueAssetReleaseTitleValidator extends ConstraintValidator {

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

    // Check if the entity is a release.
    if (!empty($entity->get('field_isr_is_version_of')->getValue()[0]['target_id'])) {
      // Get the asset_release this entity belongs to.
      $parent = Rdf::load($entity->get('field_isr_is_version_of')
        ->getValue()[0]['target_id']);

      // The release can have the same name as the asset_release it belongs to.
      if ($parent->label() == $entity->label()) {
        return;
      }

      // The release can have the same name as the sibling releases.
      foreach ($parent->get('field_isr_has_version')->getValue() as $release) {
        $sibling = Rdf::load($release['target_id']);
        if ($entity->label() == $sibling->label()) {
          return;
        }
      }
    }

    $query = \Drupal::entityQuery($entity_type_id)
      // The id could be NULL, so we cast it to 0 in that case.
      ->condition($id_key, (int) $items->getEntity()->id(), '<>')
      ->condition($field_name, $item->value)
      ->condition('rid', 'asset_release');
    // @todo: Discuss about it whether we need it.
    if (empty($entity->get('field_isr_is_version_of')->getValue()[0]['target_id'])) {
      $query->notExists('field_isr_is_version_of');
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

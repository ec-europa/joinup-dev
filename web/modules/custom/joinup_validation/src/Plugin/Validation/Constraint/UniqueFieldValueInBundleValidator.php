<?php

declare(strict_types = 1);

namespace Drupal\joinup_validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that a field is unique for the given entity type within a bundle.
 */
class UniqueFieldValueInBundleValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new UniqueFieldValueInBundleValidator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

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

    $query = $this->entityTypeManager->getStorage($entity_type_id)->getQuery()
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

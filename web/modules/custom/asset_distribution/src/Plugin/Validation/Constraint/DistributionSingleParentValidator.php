<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Provides a validator for the 'DistributionSingleParent' constraint.
 */
class DistributionSingleParentValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new validator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($field_item_list, Constraint $constraint): void {
    /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field_item_list */
    if ($field_item_list->isEmpty()) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('rdf_entity');
    $entity = $field_item_list->getEntity();

    $distribution_ids = [];
    foreach ($field_item_list as $field_item) {
      $distribution_ids[] = $field_item->target_id;
    }

    $query = ($storage->getQuery())
      ->condition('rid', ['solution', 'asset_release'], 'IN');
    if ($entity->id()) {
      $query->condition('id', $entity->id(), '<>');
    }

    /** @var \Drupal\rdf_entity\RdfInterface $distribution */
    foreach ($storage->loadMultiple($distribution_ids) as $distribution_id => $distribution) {
      $ids = (clone $query)
        ->condition($query->orConditionGroup()
          ->condition('field_is_distribution', $distribution_id)
          ->condition('field_isr_distribution', $distribution_id)
        )->execute();

      if ($ids) {
        /** @var \Drupal\rdf_entity\RdfInterface $parent */
        foreach ($storage->loadMultiple($ids) as $parent) {
          $this->context->addViolation($constraint->message, [
            '%label' => $this->buildLabel($distribution),
            '%parent' => $this->buildLabel($parent),
            '@bundle' => $parent->get('rid')->entity->getSingularLabel(),
          ]);
        }
      }
    }
  }

  /**
   * Builds a RDF entity label.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The RDF entity.
   *
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   The label.
   */
  protected function buildLabel(RdfInterface $entity) {
    return $entity->label() ?: $entity->id();
  }

}

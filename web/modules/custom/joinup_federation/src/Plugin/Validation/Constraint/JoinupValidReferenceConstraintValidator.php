<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Replaces the core ValidReferenceConstraintValidator validator.
 *
 * The method ::validate() is forked from the parent class with only two lines
 * changed and commented as:
 * - Change #1: Line changed.
 * - Change #2: Line changed.
 */
class JoinupValidReferenceConstraintValidator extends ValidReferenceConstraintValidator {

  use JoinupEntityReferenceConstraintTrait;

  /**
   * Builds a new validator instance.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The selection plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs
   *   The staging candidate graphs service.
   */
  public function __construct(SelectionPluginManagerInterface $selection_manager, EntityTypeManagerInterface $entity_type_manager, StagingCandidateGraphsInterface $staging_candidate_graphs) {
    parent::__construct($selection_manager, $entity_type_manager);
    $this->stagingCandidateGraphs = $staging_candidate_graphs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_type.manager'),
      $container->get('joinup_federation.staging_candidate_graphs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    /** @var \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraint $constraint */
    if (!isset($value)) {
      return;
    }

    // Collect new entities and IDs of existing entities across the field items.
    $new_entities = [];
    $target_ids = [];
    foreach ($value as $delta => $item) {
      $target_id = $item->target_id;
      // We don't use a regular NotNull constraint for the target_id property as
      // NULL is allowed if the entity property contains an unsaved entity.
      // @see \Drupal\Core\TypedData\DataReferenceTargetDefinition::getConstraints()
      if (!$item->isEmpty() && $target_id === NULL) {
        if (!$item->entity->isNew()) {
          $this->context->buildViolation($constraint->nullMessage)
            ->atPath((string) $delta)
            ->addViolation();
          return;
        }
        $new_entities[$delta] = $item->entity;
      }

      // '0' or NULL are considered valid empty references.
      if (!empty($target_id)) {
        $target_ids[$delta] = $target_id;
      }
    }

    // Early opt-out if nothing to validate.
    if (!$new_entities && !$target_ids) {
      return;
    }

    $entity = !empty($value->getParent()) ? $value->getEntity() : NULL;

    /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
    $handler = $this->selectionManager->getSelectionHandler($value->getFieldDefinition(), $entity);
    $target_type_id = $value->getFieldDefinition()->getSetting('target_type');

    // Add violations on deltas with a new entity that is not valid.
    if ($new_entities) {
      if ($handler instanceof SelectionWithAutocreateInterface) {
        $valid_new_entities = $handler->validateReferenceableNewEntities($new_entities);
        $invalid_new_entities = array_diff_key($new_entities, $valid_new_entities);
      }
      else {
        // If the selection handler does not support referencing newly created
        // entities, all of them should be invalidated.
        $invalid_new_entities = $new_entities;
      }

      foreach ($invalid_new_entities as $delta => $entity) {
        $this->context->buildViolation($constraint->invalidAutocreateMessage)
          ->setParameter('%type', $target_type_id)
          ->setParameter('%label', $entity->label())
          ->atPath((string) $delta . '.entity')
          ->setInvalidValue($entity)
          ->addViolation();
      }
    }

    // Add violations on deltas with a target_id that is not valid.
    if ($target_ids) {
      // Get a list of pre-existing references.
      $previously_referenced_ids = [];
      if ($value->getParent() && ($entity = $value->getEntity()) && !$entity->isNew()) {
        // Change #1: Line changed.
        $existing_entity = $this->loadUnchanged($entity);
        foreach ($existing_entity->{$value->getFieldDefinition()->getName()}->getValue() as $item) {
          $previously_referenced_ids[$item['target_id']] = $item['target_id'];
        }
      }

      $valid_target_ids = $handler->validateReferenceableEntities($target_ids);
      if ($invalid_target_ids = array_diff($target_ids, $valid_target_ids)) {
        // For accuracy of the error message, differentiate non-referenceable
        // and non-existent entities.
        // Change #2: Line changed.
        $existing_entities = $this->loadMultiple($target_type_id, $invalid_target_ids, $entity);
        foreach ($invalid_target_ids as $delta => $target_id) {
          // Check if any of the invalid existing references are simply not
          // accessible by the user, in which case they need to be excluded from
          // validation.
          if (isset($previously_referenced_ids[$target_id]) && isset($existing_entities[$target_id]) && !$existing_entities[$target_id]->access('view')) {
            continue;
          }

          $message = isset($existing_entities[$target_id]) ? $constraint->message : $constraint->nonExistingMessage;
          $this->context->buildViolation($message)
            ->setParameter('%type', $target_type_id)
            ->setParameter('%id', $target_id)
            ->atPath((string) $delta . '.target_id')
            ->setInvalidValue($target_id)
            ->addViolation();
        }
      }
    }
  }

  /**
   * Returns a list of referenced entities.
   *
   * Pass the list of graphs when all of the following assertions are true,
   * otherwise, use the core loader:
   * - The target entities are 'rdf_entity' entities,
   * - The host entity is an 'rdf_entity' entity type;
   * - The host entity is in the 'staging' graph,
   *
   * @param string $target_type_id
   *   The entity type ID of the target entities.
   * @param array $ids
   *   A list of entity IDs to be retrieved.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The host entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   A list of referred entities.
   */
  protected function loadMultiple(string $target_type_id, array $ids, EntityInterface $entity): array {
    $storage = $this->entityTypeManager->getStorage($target_type_id);
    if ($target_type_id === 'rdf_entity' && $entity->getEntityTypeId() === 'rdf_entity' && $entity->get('graph')->target_id === 'staging') {
      /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
      return $storage->loadMultiple($ids, $this->stagingCandidateGraphs->getCandidates());
    }
    return $storage->loadMultiple($ids);
  }

}

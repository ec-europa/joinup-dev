<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionWithAutocreateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Replaces the core ValidReferenceConstraintValidator validator.
 */
class JoinupValidReferenceConstraintValidator extends ValidReferenceConstraintValidator {

  /**
   * The staging candidate graphs service.
   *
   * @var \Drupal\joinup_federation\StagingCandidateGraphsInterface
   */
  protected $stagingCandidateGraphs;

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

    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = !empty($value->getParent()) ? $value->getEntity() : NULL;

    // When all of the following assertions are true:
    // - The host entity exists;
    // - The host entity is an 'rdf_entity' entity type;
    // - The host entity is in the 'staging' graph;
    // - The target entities are 'rdf_entity' entities,
    // use a slightly changed validator.
    // @see self::validateStagingGraph()
    if ($entity && ($entity->getEntityTypeId() === 'rdf_entity') && ($entity->get('graph')->target_id === 'staging')) {
      $target_entity_type_id = $value->getFieldDefinition()->getSetting('target_type');
      if ($target_entity_type_id === 'rdf_entity') {
        $this->validateStagingGraph($value, $constraint, $entity);
      }
    }

    parent::validate($value, $constraint);
  }

  /**
   * Validates an RDF entity reference for RDF host entities in staging graph.
   *
   * Most of this method's code was copied from the parent ::validate() method.
   * This is a slightly adapted version that adds the 'staging' graph on top
   * of the default graph IDs list to be used with the RDF entity loaders when
   * all of the following assertions are true:
   * - The host entity exists.
   * - The host entity is an 'rdf_entity' entity type.
   * - The host entity is in the 'staging' graph.
   * - The target entities are 'rdf_entity' entities.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $value
   *   The value to be validated.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   The constraint object.
   * @param \Drupal\rdf_entity\RdfInterface $rdf_entity
   *   The RDF host entity.
   *
   * @see \Drupal\Core\Entity\Plugin\Validation\Constraint\ValidReferenceConstraintValidator::validate()
   */
  protected function validateStagingGraph(FieldItemListInterface $value, Constraint $constraint, RdfInterface $rdf_entity): void {
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

    /** @var \Drupal\joinup_federation\Plugin\EntityReferenceSelection\RdfEntitySelection $handler */
    $handler = $this->selectionManager->getSelectionHandler($value->getFieldDefinition(), $rdf_entity);

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

      foreach ($invalid_new_entities as $delta => $rdf_entity) {
        $this->context->buildViolation($constraint->invalidAutocreateMessage)
          ->setParameter('%type', 'rdf_entity')
          ->setParameter('%label', $rdf_entity->label())
          ->atPath((string) $delta . '.entity')
          ->setInvalidValue($rdf_entity)
          ->addViolation();
      }
    }

    // Add violations on deltas with a target_id that is not valid.
    if ($target_ids) {
      /** @var \Drupal\rdf_entity\RdfEntitySparqlStorageInterface $rdf_entity_storage */
      $rdf_entity_storage = $this->entityTypeManager->getStorage('rdf_entity');

      // Get a list of pre-existing references.
      $previously_referenced_ids = [];
      if ($value->getParent() && ($rdf_entity = $value->getEntity()) && !$rdf_entity->isNew()) {
        $existing_entity = $rdf_entity_storage->loadUnchanged($rdf_entity->id(), $this->stagingCandidateGraphs->getCandidates());
        foreach ($existing_entity->{$value->getFieldDefinition()->getName()}->getValue() as $item) {
          $previously_referenced_ids[$item['target_id']] = $item['target_id'];
        }
      }

      $valid_target_ids = $handler->validateReferenceableEntities($target_ids);
      if ($invalid_target_ids = array_diff($target_ids, $valid_target_ids)) {
        // For accuracy of the error message, differentiate non-referenceable
        // and non-existent entities.
        $existing_entities = $rdf_entity_storage->loadMultiple($invalid_target_ids, $this->stagingCandidateGraphs->getCandidates());
        foreach ($invalid_target_ids as $delta => $target_id) {
          // Check if any of the invalid existing references are simply not
          // accessible by the user, in which case they need to be excluded from
          // validation.
          if (isset($previously_referenced_ids[$target_id]) && isset($existing_entities[$target_id]) && !$existing_entities[$target_id]->access('view')) {
            continue;
          }

          $message = isset($existing_entities[$target_id]) ? $constraint->message : $constraint->nonExistingMessage;
          $this->context->buildViolation($message)
            ->setParameter('%type', 'rdf_entity')
            ->setParameter('%id', $target_id)
            ->atPath((string) $delta . '.target_id')
            ->setInvalidValue($target_id)
            ->addViolation();
        }
      }
    }
  }

}

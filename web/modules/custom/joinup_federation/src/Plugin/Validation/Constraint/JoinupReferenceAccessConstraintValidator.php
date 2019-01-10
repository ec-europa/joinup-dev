<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\ReferenceAccessConstraintValidator;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Replaces the core ValidReferenceConstraintValidator validator.
 *
 * The method ::validate() is forked from the parent class with only one line
 * changed and commented as: "Line changed compared to parent class."
 */
class JoinupReferenceAccessConstraintValidator extends ReferenceAccessConstraintValidator implements ContainerInjectionInterface {

  use JoinupEntityReferenceConstraintTrait;

  /**
   * Builds a new validator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs
   *   The staging candidate graphs service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StagingCandidateGraphsInterface $staging_candidate_graphs) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stagingCandidateGraphs = $staging_candidate_graphs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_federation.staging_candidate_graphs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    /* @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }
    $id = $value->target_id;
    // '0' or NULL are considered valid empty references.
    if (empty($id)) {
      return;
    }
    /* @var \Drupal\Core\Entity\FieldableEntityInterface $referenced_entity */
    $referenced_entity = $value->entity;
    if ($referenced_entity) {
      $entity = $value->getEntity();
      $check_permission = TRUE;
      if (!$entity->isNew()) {
        // Line changed compared to parent class.
        $existing_entity = $this->loadUnchanged($entity);
        $referenced_entities = $existing_entity->{$value->getFieldDefinition()->getName()}->referencedEntities();
        // Check permission if we are not already referencing the entity.
        foreach ($referenced_entities as $ref) {
          if (isset($referenced_entities[$ref->id()])) {
            $check_permission = FALSE;
            break;
          }
        }
      }
      // We check that the current user had access to view any newly added
      // referenced entity.
      if ($check_permission && !$referenced_entity->access('view')) {
        $type = $value->getFieldDefinition()->getSetting('target_type');
        $this->context->addViolation($constraint->message, ['%type' => $type, '%id' => $id]);
      }
    }
  }

}

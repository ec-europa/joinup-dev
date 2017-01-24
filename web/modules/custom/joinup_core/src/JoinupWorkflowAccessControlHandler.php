<?php

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Og;

/**
 * Access handler for entities with a workflow.
 *
 * @todo: More information should be inserted here.
 * @todo: If we are going with a unified way, a readme should include the
 *  workflow creation process.
 *
 * @package Drupal\joinup_core
 */
class JoinupWorkflowAccessControlHandler {

  const STATE_FIELD = 'field_state';
  const PRE_MODERATION = 1;
  const POST_MODERATION = 0;
  const ELIBRARY_ONLY_FACILITATORS = 0;
  const ELIBRARY_MEMBERS_FACILITATORS = 1;
  const ELIBRARY_REGISTERED_USERS = 2;
  const WORKFLOW_DEFAULT = 'default';
  const WORKFLOW_PRE_MODERATED = 'pre_moderated';
  const WORKFLOW_POST_MODERATED = 'post_moderated';

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManager
   */
  private $membershipManager;

  /**
   * Constructs a JoinupDocumentRelationManager object.
   *
   * @param \Drupal\og\MembershipManagerInterface $ogMembershipManager
   *   The OG membership manager service.
   */
  public function __construct(MembershipManagerInterface $ogMembershipManager) {
    $this->membershipManager = $ogMembershipManager;
  }

  // @todo: We need to check CRUD access.
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ((!in_array($entity->getEntityTypeId(), ['rdf_entity', 'node']))) {
      return AccessResult::neutral();
    }

    switch ($operation) {
      case 'view':
        $parent = $this->getEntityParent($entity);
        $access_handler = \Drupal::service('entity_type.manager')->getAccessControlHandler('rdf_entity');
        if (!$access_handler->access($parent, 'view', $account)) {
          return AccessResult::forbidden();
        }

        $membership = Og::getMembership($parent, $account);
        if (empty($membership)) {
          return AccessResult::neutral();
        }

        if (!$entity->isPublished() && $membership->hasPermission('view any unpublished content')) {
          return AccessResult::allowed();
        }
        break;

      case 'create':
      case 'update':
        $allowed_transitions = $entity->get('field_state')->first()->getTransitions();
        return AccessResult::allowedIf(!empty($allowed_transitions));

      case 'delete':
        // @todo: This is weird and might not work.
        // The authenticated user can only delete his own document.
        // In a pre moderated workflow, he will not be able to do that either.
        $workflow = $this->getEntityWorkflow($entity);
        if ($workflow === self::WORKFLOW_PRE_MODERATED) {
          return AccessResult::allowedIfHasPermission($account, 'delete any document content');
        }

    }

    return AccessResult::neutral();
  }

  /**
   * Retrieves the parent of the entity.
   *
   * @todo: This might need to go to joinup guard class base.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The document node.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the entity belongs to, or NULL when no group is found.
   */
  public function getEntityParent(EntityInterface $entity) {
    $groups = $this->membershipManager->getGroups($entity);

    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

  /**
   * Returns the appropriate workflow to use for the passed entity.
   *
   * If the entity is a group, then the workflow is the default workflow.
   * If the entity is a group content, then the workflow is dependant to the
   * moderation settings of the parent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity.
   *
   * @return string
   *    The id of the workflow to use.
   */
  public function getEntityWorkflow(EntityInterface $entity) {
    if (Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      return self::WORKFLOW_DEFAULT;
    }

    $parent = $this->getEntityParent($entity);
    if (empty($parent) || in_array($parent->bundle(), ['collection', 'solution'])) {
      return self::WORKFLOW_PRE_MODERATED;
    }
    $fields = [
      'collection' => 'field_ar_moderation',
      'solution' => 'field_is_moderation',
    ];

    $moderation = $parent->{$fields[$parent->bundle()]}->value;
    $workflow_id = $moderation == TRUE ? self::WORKFLOW_PRE_MODERATED : self::WORKFLOW_POST_MODERATED;
    return $workflow_id;
  }

}
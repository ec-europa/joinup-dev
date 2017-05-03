<?php

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\Og;

/**
 * Access handler for entities with a workflow.
 *
 * @todo: More information should be inserted here.
 * @todo: If we are going with a unified way, a readme should include the
 *  workflow creation process.
 * @todo: Add cacheability to all access.
 *
 * @package Drupal\joinup_core
 */
class NodeWorkflowAccessControlHandler {

  /**
   * The state field machine name.
   */
  const STATE_FIELD = 'field_state';

  /**
   * Flag for pre-moderated groups.
   */
  const PRE_MODERATION = 1;

  /**
   * Flag for post-moderated groups.
   */
  const POST_MODERATION = 0;

  /**
   * The machine name of the default workflow for groups.
   *
   * @todo: Change the group workflows to 'default'.
   */
  const WORKFLOW_DEFAULT = 'default';

  /**
   * The machine name of the pre moderated workflow for group content.
   *
   * @todo: Backport this to entity types other than document.
   */
  const WORKFLOW_PRE_MODERATED = 'pre_moderated';

  /**
   * The machine name of the post moderated workflow for group content.
   *
   * @todo: Backport this to entity types other than document.
   */
  const WORKFLOW_POST_MODERATED = 'post_moderated';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManager
   */
  protected $membershipManager;

  /**
   * The discussions relation manager.
   *
   * @var \Drupal\joinup_core\JoinupRelationManager
   */
  protected $relationManager;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a JoinupDocumentRelationManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The OG membership manager service.
   * @param \Drupal\joinup_core\JoinupRelationManager $relation_manager
   *   The relation manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $og_membership_manager, JoinupRelationManager $relation_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $og_membership_manager;
    $this->relationManager = $relation_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Main handler for access checks for group content in joinup.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity object.
   * @param string $operation
   *   The CRUD operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The result of the access check.
   */
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account = NULL) {
    if ($account === NULL) {
      $account = $this->currentUser;
    }

    if ($entity->getEntityTypeId() !== 'node') {
      return AccessResult::neutral();
    }

    switch ($operation) {
      case 'view':
        return $this->entityViewAccess($entity, $account);

      case 'create':
      case 'update':
        $allowed_transitions = \Drupal::service('joinup_core.workflow.helper')->getAvailableTransitions($entity, $account);
        return empty($allowed_transitions) ? AccessResult::forbidden() : AccessResult::allowed();

      case 'delete':
        return $this->entityDeleteAccess($entity, $account);

      case 'post comments':
        $parent_state = $this->relationManager->getParentState($entity);
        // Commenting on content of an archived group is not allowed.
        if ($parent_state === 'archived') {
          return AccessResult::forbidden();
        }
        else {
          $parent = $this->relationManager->getParent($entity);
          $membership = $this->membershipManager->getMembership($parent, $account);
          if ($membership instanceof OgMembership) {
            AccessResult::allowedIf($membership->hasPermission($operation));
          }
        }
    }

    return AccessResult::neutral();
  }

  /**
   * Access check for the view operation.
   *
   * The following checks take place with the same priority:
   * - If the parent entity is not viewable, only moderators, facilitators and
   *   the node owner can see the entity regardless of the state.
   * - If the user has the permission in the membership to view all unpublished
   *   content, he is granted access.
   * - Otherwise we return neutral.
   *
   * Note that admin permissions are already checked in the entity access
   * handler class.
   * If we return neutral, the entity access handler class, will automatically
   * take care of the user being able to view his own unpublished content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result check.
   */
  protected function entityViewAccess(EntityInterface $entity, AccountInterface $account) {
    $parent = $this->getEntityParent($entity);
    $access_handler = $this->entityTypeManager->getAccessControlHandler('rdf_entity');
    if (!$access_handler->access($parent, 'view', $account)) {
      // Anonymous users do not have access to content of non published groups.
      return AccessResult::forbiddenIf($account->isAnonymous() || $entity->getOwnerId() !== $account->id());
    }

    $membership = Og::getMembership($parent, $account);
    if (empty($membership)) {
      return AccessResult::neutral();
    }

    $entity_type = ($entity->getEntityTypeId() === 'node') ? 'content' : 'rdf_entity';
    if (!$entity->isPublished() && $membership->hasPermission("view any unpublished {$entity_type}")) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

  /**
   * Access check for delete operation.
   *
   * Delete operation should not be interrupted by states. The user either has
   * or has not the permission.
   * The following checks take place with the given priority:
   * - If the user has global permission to delete any entity of the given
   *   bundle, he is being granted access.
   * - If the user has group permission to delete any entity of the given
   *   bundle, he is being granted access.
   * - If the user is the owner of the entity, he is allowed only if the parent
   *   is in a post moderated state.
   *
   * In all other cases, we allow entity access handler to decide.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function entityDeleteAccess(EntityInterface $entity, AccountInterface $account) {
    $entity_type = ($entity->getEntityTypeId() === 'node') ? 'content' : 'rdf_entity';
    if ($account->hasPermission("delete any {$entity->bundle()} {$entity_type}")) {
      return AccessResult::allowed();
    }

    $parent = $this->getEntityParent($entity);
    $membership = Og::getMembership($parent, $account);
    if (!empty($membership) && $membership->hasPermission("delete any {$entity->bundle()} {$entity_type}")) {
      return AccessResult::allowed();
    }

    $moderation = $this->relationManager->getParentModeration($entity);
    // If the parent is in pre-moderated state, the user can only delete the
    // entity if he has the 'delete all' permission because owners are not
    // allowed to.
    // Access is denied because if neutral is returned, the default entity
    // access control handler will allow it.
    if ($moderation == self::PRE_MODERATION) {
      return AccessResult::forbiddenIf(!$account->hasPermission("delete any {$entity->bundle()} {$entity_type}"));
    }

    return AccessResult::neutral();
  }

  /**
   * Helper method to retrieve the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface|null
   *   The rdf entity the entity belongs to, or NULL when no group is found.
   */
  protected function getEntityParent(EntityInterface $entity) {
    $groups = $this->membershipManager->getGroups($entity);

    if (empty($groups['rdf_entity'])) {
      return NULL;
    }

    return reset($groups['rdf_entity']);
  }

  /**
   * Returns the appropriate workflow to use for the passed entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return string
   *   The id of the workflow to use.
   */
  protected function getEntityWorkflow(EntityInterface $entity) {
    $workflow = $entity->field_state->first()->getWorkflow();
    return $workflow->getId();
  }

}

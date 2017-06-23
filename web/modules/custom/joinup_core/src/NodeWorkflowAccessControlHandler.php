<?php

namespace Drupal\joinup_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Access handler for entities with a workflow.
 *
 * @todo: More information should be inserted here.
 * @todo: If we are going with a unified way, a readme should include the
 *  workflow creation process.
 * @todo: Add cacheability to all access.
 *
 * All parameters for the permissions are described in the permission scheme.
 *
 * @see joinup_community_content.permission_scheme.yml
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
   * The workflow helper class.
   *
   * @var \Drupal\joinup_core\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * The permission scheme stored in configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $permissionScheme;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $og_membership_manager, JoinupRelationManager $relation_manager, AccountInterface $current_user, ConfigFactoryInterface $config_factory, WorkflowHelperInterface $workflow_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $og_membership_manager;
    $this->relationManager = $relation_manager;
    $this->currentUser = $current_user;
    $this->workflowHelper = $workflow_helper;
    $this->permissionScheme = $config_factory->get('joinup_community_content.permission_scheme');
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

    // In case of neutral (no parent) or forbidden (no access), return the
    // result.
    $access = $this->hasParentViewAccess($entity, $account);
    if (!$access->isAllowed()) {
      return $access;
    }

    // For entities that do not have a published version and are in draft state,
    // only the owner has access.
    if (!$this->hasPublishedVersion($entity) && $this->getEntityState($entity) === 'draft' && $entity->getOwnerId() !== $account->id()) {
      return AccessResult::forbidden();
    }

    switch ($operation) {
      case 'view':
        return $this->entityViewAccess($entity, $account);

      case 'create':
        return $this->entityCreateAccess($entity, $account);

      case 'update':
        return $this->entityUpdateAccess($entity, $account);

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
   * Returns whether the user has view permissions to the parent of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user that the permission access is checked.
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function hasParentViewAccess(EntityInterface $entity, AccountInterface $account) {
    $parent = $this->getEntityParent($entity);
    // Let parent-less nodes (e.g. newsletters) be handled by the core access.
    if (empty($parent)) {
      return AccessResult::neutral();
    }

    $access_handler = $this->entityTypeManager->getAccessControlHandler('rdf_entity');
    $access = $access_handler->access($parent, 'view', $account);
    return $access ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Access check for the 'view' operation.
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
    $view_scheme = $this->permissionScheme->get('view');
    $workflow_id = $this->getEntityWorkflowId($entity);
    $state = $this->getEntityState($entity);
    return $this->userHasOwnAnyRoles($entity, $account, $view_scheme[$workflow_id][$state]) ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Access check for the 'create' operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result check.
   */
  protected function entityCreateAccess(EntityInterface $entity, AccountInterface $account) {
    $create_scheme = $this->permissionScheme->get('create');
    $workflow_id = $this->getEntityWorkflowId($entity);
    $e_library = $this->getEntityElibrary($entity);

    foreach ($create_scheme[$workflow_id][$e_library] as $transition_id => $ownership_data) {
      // There is no check whether the transition is allowed as only allowed
      // transitions are mapped in the permission scheme configuration object.
      if ($this->userHasRoles($entity, $account, $ownership_data)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * Access check for the 'update' operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result check.
   */
  protected function entityUpdateAccess(EntityInterface $entity, AccountInterface $account) {
    $update_scheme = $this->permissionScheme->get('update');
    $workflow_id = $this->getEntityWorkflowId($entity);
    $allowed_transitions = $this->workflowHelper->getAvailableTransitions($entity, $account);
    $transition_ids = array_map(function (WorkflowTransition $transition) {
      return $transition->getId();
    }, $allowed_transitions);

    foreach ($transition_ids as $transition_id) {
      if ($this->userHasOwnAnyRoles($entity, $account, $update_scheme[$workflow_id][$transition_id])) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * Access check for 'delete' operation.
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
    $delete_scheme = $this->permissionScheme->get('delete');
    $workflow_id = $this->getEntityWorkflowId($entity);
    $state = $this->getEntityState($entity);

    if (isset($delete_scheme[$state]) && $this->userHasOwnAnyRoles($entity, $account, $delete_scheme[$workflow_id][$state])) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Checks whether the user has at least one of the provided roles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param array $roles
   *   A list of role ids indexed by keys 'own' and 'any' which represents
   *   ownership and a second level of 'roles' for system roles and
   *   'og_roles' for og roles.
   *
   * @return bool
   *   True if the user has at least one of the roles provided.
   */
  protected function userHasOwnAnyRoles(EntityInterface $entity, AccountInterface $account, array $roles) {
    $own = $entity->getOwnerId() === $account->id();
    if (isset($roles['any']) && $this->userHasRoles($entity, $account, $roles['any'])) {
      return TRUE;
    }
    if ($own && isset($roles['own']) && $this->userHasRoles($entity, $account, $roles['own'])) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Checks whether the user has at least one of the provided roles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param array $roles
   *   A list of role ids indexed by 'roles' for system roles and
   *   'og_roles' for og roles.
   *
   * @return bool
   *   True if the user has at least one of the roles provided.
   */
  protected function userHasRoles(EntityInterface $entity, AccountInterface $account, array $roles) {
    $parent = $this->getEntityParent($entity);
    $membership = $this->membershipManager->getMembership($parent, $account);

    // First check the 'any' permissions.
    if (isset($roles['roles'])) {
      if (array_intersect($account->getRoles(), $roles['roles'])) {
        return TRUE;
      }
    }
    if (isset($roles['og_roles']) && !empty($membership)) {
      if (array_intersect($membership->getRolesIds(), $roles['og_roles'])) {
        return TRUE;
      }
    }

    return FALSE;
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
  protected function getEntityWorkflowId(EntityInterface $entity) {
    $workflow = $entity->{self::STATE_FIELD}->first()->getWorkflow();
    return $workflow->getId();
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
  protected function getEntityState(EntityInterface $entity) {
    return $entity->{self::STATE_FIELD}->first()->value;
  }

  /**
   * Returns the value of the eLibrary settings of the parent of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   *
   * @return array
   *   An array of roles that are allowed.
   */
  protected function getEntityElibrary(EntityInterface $entity) {
    $parent = $this->relationManager->getParent($entity);
    $e_library_name = $this->getParentElibraryName($parent);
    return $parent->{$e_library_name}->value;
  }

  /**
   * Returns the eLibrary creation field's machine name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   *
   * @return string
   *   The machine name of the eLibrary creation field.
   */
  protected function getParentElibraryName(EntityInterface $entity) {
    $field_array = [
      'collection' => 'field_ar_elibrary_creation',
      'solution' => 'field_is_elibrary_creation',
    ];

    return $field_array[$entity->bundle()];
  }

  /**
   * Checks whether the entity has a published version.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the entity has a published version.
   */
  protected function hasPublishedVersion(EntityInterface $entity) {
    if ($entity->isNew()) {
      return FALSE;
    }
    if ($entity->isPublished()) {
      return TRUE;
    }
    $published = $this->entityTypeManager->getStorage('node')->load($entity->id());
    return $published->isPublished();
  }

}

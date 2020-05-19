<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeStorageInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;

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
class CommunityContentWorkflowAccessControlHandler {

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The workflow helper class.
   *
   * @var \Drupal\joinup_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * Constructs a new CommunityContentWorkflowAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The OG membership manager service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $og_membership_manager, AccountInterface $current_user, ConfigFactoryInterface $config_factory, WorkflowHelperInterface $workflow_helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $og_membership_manager;
    $this->currentUser = $current_user;
    $this->workflowHelper = $workflow_helper;
    $this->configFactory = $config_factory;
  }

  /**
   * Main handler for access checks for group content in Joinup.
   *
   * @param \Drupal\joinup_community_content\Entity\CommunityContentInterface $node
   *   The group content entity object.
   * @param string $operation
   *   The CRUD operation.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of the access check.
   */
  public function entityAccess(CommunityContentInterface $node, $operation, ?AccountInterface $account = NULL): AccessResultInterface {
    if ($account === NULL) {
      $account = $this->currentUser;
    }

    if (!$node instanceof CommunityContentInterface) {
      return AccessResult::neutral();
    }

    // On neutral (no parent) or forbidden (no access) return the result.
    $access = $this->hasParentViewAccess($node, $account);
    if (!$access->isAllowed()) {
      return $access;
    }

    // For entities that do not have a published version and are in draft state,
    // only the owner has access. This access restriction does not apply to
    // moderators.
    if (
      !$account->hasPermission('access draft community content')
      && !$this->hasPublishedVersion($node)
      && $this->getEntityState($node) === 'draft'
      && $node->getOwnerId() !== $account->id()
    ) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    switch ($operation) {
      case 'view':
        return $this->entityViewAccess($node, $account);

      case 'create':
        return $this->entityCreateAccess($node, $account);

      case 'update':
        return $this->entityUpdateAccess($node, $account);

      case 'delete':
        return $this->entityDeleteAccess($node, $account);

      case 'post comments':
        $parent = $node->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->entity;
        $parent_state = JoinupGroupHelper::getState($parent);
        $entity_state = $this->getEntityState($node);

        // Commenting on content of an archived group is not allowed.
        if ($parent_state === 'archived' || $entity_state === 'archived') {
          return AccessResult::forbidden();
        }

        $membership = $this->membershipManager->getMembership($parent, $account->id());
        if ($membership instanceof OgMembership) {
          return AccessResult::allowedIf($membership->hasPermission($operation));
        }
    }

    return AccessResult::neutral();
  }

  /**
   * Returns whether the user has view permissions to the parent of the entity.
   *
   * @param \Drupal\joinup_community_content\Entity\CommunityContentInterface $node
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user that the permission access is checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function hasParentViewAccess(CommunityContentInterface $node, AccountInterface $account): AccessResultInterface {
    $group = $node->getGroup();
    $access_handler = $this->entityTypeManager->getAccessControlHandler('rdf_entity');
    $access = $access_handler->access($group, 'view', $account);
    $result = $access ? AccessResult::allowed() : AccessResult::forbidden();
    return $result->addCacheableDependency($group);
  }

  /**
   * Access check for the 'view' operation.
   *
   * @param \Drupal\joinup_community_content\Entity\CommunityContentInterface $node
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result check.
   */
  protected function entityViewAccess(CommunityContentInterface $node, AccountInterface $account): AccessResultInterface {
    $view_scheme = $this->getPermissionScheme('view');
    $workflow_id = $this->getEntityWorkflowId($node);
    $state = $this->getEntityState($node);
    $result = $this->workflowHelper->userHasOwnAnyRoles($node, $account, $view_scheme[$workflow_id][$state]) ? AccessResult::allowed() : AccessResult::forbidden();
    return $result->addCacheableDependency($node);
  }

  /**
   * Access check for the 'create' operation.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result check.
   */
  protected function entityCreateAccess(NodeInterface $entity, AccountInterface $account): AccessResultInterface {
    $create_scheme = $this->getPermissionScheme('create');
    $workflow_id = $this->getEntityWorkflowId($entity);
    $content_creation = $this->getParentContentCreationOption($entity);

    foreach ($create_scheme[$workflow_id][$content_creation] as $ownership_data) {
      // There is no check whether the transition is allowed as only allowed
      // transitions are mapped in the permission scheme configuration object.
      if ($this->workflowHelper->userHasRoles($entity, $account, $ownership_data)) {
        return AccessResult::allowed();
      }
    }
    return AccessResult::forbidden();
  }

  /**
   * Access check for the 'update' operation.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result check.
   */
  protected function entityUpdateAccess(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    $allowed_states = $this->workflowHelper->getAvailableTargetStates($node, $account);
    if (empty($allowed_states)) {
      return AccessResult::forbidden();
    }
    return AccessResult::allowed()->addCacheableDependency($node);
  }

  /**
   * Access check for 'delete' operation.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  protected function entityDeleteAccess(NodeInterface $entity, AccountInterface $account): AccessResult {
    $delete_scheme = $this->getPermissionScheme('delete');
    $workflow_id = $this->getEntityWorkflowId($entity);
    $state = $this->getEntityState($entity);

    if (isset($delete_scheme[$workflow_id][$state]) && $this->workflowHelper->userHasOwnAnyRoles($entity, $account, $delete_scheme[$workflow_id][$state])) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Returns the appropriate workflow to use for the passed entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The group content entity.
   *
   * @return string
   *   The id of the workflow to use.
   */
  protected function getEntityWorkflowId(NodeInterface $entity): string {
    $workflow = $entity->{self::STATE_FIELD}->first()->getWorkflow();
    return $workflow->getId();
  }

  /**
   * Returns the appropriate workflow to use for the passed entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The group content entity.
   *
   * @return string
   *   The id of the workflow to use.
   */
  protected function getEntityState(NodeInterface $entity): string {
    return $entity->{self::STATE_FIELD}->first()->value;
  }

  /**
   * Returns the content creation option value of the parent of an entity.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The group content entity.
   *
   * @return array
   *   The content creation option value.
   */
  protected function getParentContentCreationOption(NodeInterface $entity): string {
    $parent = JoinupGroupHelper::getGroup($entity);
    return JoinupGroupHelper::getContentCreation($parent);
  }

  /**
   * Checks whether the entity has a published version.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The entity object.
   *
   * @return bool
   *   Whether the entity has a published version.
   */
  protected function hasPublishedVersion(NodeInterface $entity): bool {
    if ($entity->isNew()) {
      return FALSE;
    }
    if ($entity->isPublished()) {
      return TRUE;
    }
    $published = $this->getNodeStorage()->load($entity->id());
    if (!empty($published) && $published instanceof EntityPublishedInterface) {
      return $published->isPublished();
    }
    return FALSE;
  }

  /**
   * Returns the configured permission scheme for the given operation.
   *
   * @param string $operation
   *   The operation for which to return the permission scheme. Can be one of
   *   'create', 'view', 'update', 'delete'.
   *
   * @return array
   *   The permission scheme.
   */
  protected function getPermissionScheme(string $operation): array {
    \assert(\in_array($operation, ['create', 'view', 'update', 'delete']), 'A valid operation should be passed');
    return $this->configFactory->get('joinup_community_content.permission_scheme')->get($operation);
  }

  /**
   * Returns the storage handler for nodes.
   *
   * @return \Drupal\node\NodeStorageInterface
   *   The storage handler.
   */
  protected function getNodeStorage(): NodeStorageInterface {
    return $this->entityTypeManager->getStorage('node');
  }

}

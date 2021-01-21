<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Plugin\WorkflowStatePermission;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_group\Plugin\WorkflowStatePermission\GroupWorkflowStatePermissionBase;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine_permissions\StateMachinePermissionStringConstructor;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * Depending on the user role some workflow states are not available. For
 * example only the author of a community content can request deletion, and only
 * a moderator or facilitator can archive a discussion.
 *
 * @WorkflowStatePermission(
 *   id = "community_content",
 * )
 */
class CommunityContentWorkflowStatePermission extends GroupWorkflowStatePermissionBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity instanceof CommunityContentInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, WorkflowInterface $workflow, string $from_state, string $to_state): bool {
    if ($account->hasPermission($entity->getEntityType()->getAdminPermission())) {
      return TRUE;
    }

    $any_permission = StateMachinePermissionStringConstructor::constructTransitionPermission($entity->getEntityTypeId(), $entity->bundle(), $workflow, $from_state, $to_state, TRUE);
    $own_permission = StateMachinePermissionStringConstructor::constructTransitionPermission($entity->getEntityTypeId(), $entity->bundle(), $workflow, $from_state, $to_state, FALSE);
    $has_access = $account->hasPermission($any_permission) || (($entity->getOwnerId() === $account->id()) && $account->hasPermission($own_permission));
    if ($has_access) {
      return TRUE;
    }

    // No access has been given by the account permissions, check OG permissions
    // next.
    $group = $entity->getGroup();
    $has_access = $this->workflowHelper->hasOgPermission($any_permission, $group, $account)
      || ($entity->getOwnerId() === $account->id() && $this->workflowHelper->hasOgPermission($own_permission, $group, $account));

    // If the user has access to the 'request_deletion' transition but also has
    // delete permission to the entity, revoke the permission to request
    // deletion.
    if ($has_access && $to_state === 'deletion_request') {
      $has_access = !$entity->access('delete');
    }

    return $has_access;
  }

}

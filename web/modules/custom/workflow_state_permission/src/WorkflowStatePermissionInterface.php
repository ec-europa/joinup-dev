<?php

declare(strict_types = 1);

namespace Drupal\workflow_state_permission;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for services that determine permission to update workflow states.
 */
interface WorkflowStatePermissionInterface {

  /**
   * Determines whether the given user is allowed to update a workflow state.
   *
   * This can also be used to check if the entity can be updated while keeping
   * the workflow state unchanged.
   *
   * This is intended to be used in addition to checking the available workflow
   * transitions. In case the $from_state and $to_state are different, it is
   * important to call WorkflowInterface::getAllowedTransitions() and check that
   * the transition is allowed before calling this. The transitions have not
   * been built in here because the State Machine module does not yet allow to
   * retrieve transitions for a given user account. The only way to do it is to
   * swap out the current user before retrieving the transitions from the State
   * Machine. You might be looking for WorkflowHelper::getAvailableStates()
   * which takes care of this.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to perform the workflow state change.
   * @param string $from_state
   *   The initial workflow state.
   * @param string $to_state
   *   The destination state.
   *
   * @return bool
   *   TRUE if the transition is allowed, FALSE if it is not.
   *
   * @see \Drupal\joinup_workflow\WorkflowHelperInterface::getAvailableTargetStates()
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool;

}

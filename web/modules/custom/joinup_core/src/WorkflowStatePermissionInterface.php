<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

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
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool;

}

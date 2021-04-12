<?php

declare(strict_types = 1);

namespace Drupal\solution\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;

/**
 * Guard class for the transitions of the solution entity.
 */
class SolutionFulfillmentGuard implements GuardInterface {

  /**
   * Virtual state.
   */
  const NON_STATE = '__new__';

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The service that determines the access to update workflow states.
   *
   * @var \Drupal\workflow_state_permission\WorkflowStatePermissionInterface
   */
  protected $workflowStatePermission;

  /**
   * Instantiates a SolutionFulfillmentGuard service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflow_state_permission
   *   The service that determines the permission to update the workflow state
   *   for a given entity.
   */
  public function __construct(AccountInterface $current_user, WorkflowStatePermissionInterface $workflow_state_permission) {
    $this->currentUser = $current_user;
    $this->workflowStatePermission = $workflow_state_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    /** @var \Drupal\solution\Entity\SolutionInterface $entity */
    $to_state = $transition->getToState()->getId();
    // Disable virtual state.
    if ($to_state == self::NON_STATE) {
      return FALSE;
    }

    $from_state = $entity->getWorkflowState();

    // Note that we cannot call $entity->isTargetWorkflowStateAllowed() since it
    // invokes the guards, causing an endless loop.
    return $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $entity, $workflow, $from_state, $to_state);
  }

}

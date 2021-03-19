<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;

/**
 * Guard class for the transitions of the contact_information entity.
 */
class ContactInformationFulfillmentGuard implements GuardInterface {

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The workflow state permission service.
   *
   * @var \Drupal\workflow_state_permission\WorkflowStatePermission
   */
  protected $workflowStatePermission;

  /**
   * Constructs an instance of the ContactInformationFulfillmentGuard service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflow_state_permission
   *   The workflow state permission service.
   */
  public function __construct(AccountInterface $current_user, WorkflowStatePermissionInterface $workflow_state_permission) {
    $this->currentUser = $current_user;
    $this->workflowStatePermission = $workflow_state_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    /** @var \Drupal\contact_information\Entity\ContactInformationInterface $entity */
    $from_state = $entity->getWorkflowState();
    $to_state = $transition->getToState()->getId();

    // Note that we cannot call $entity->isTargetWorkflowStateAllowed() since it
    // invokes the guards, causing an endless loop.
    return $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $entity, $workflow, $from_state, $to_state);
  }

}

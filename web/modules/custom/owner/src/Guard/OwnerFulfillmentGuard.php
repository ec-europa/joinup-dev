<?php

namespace Drupal\owner\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;

/**
 * Guard class for the transitions of the owner entity.
 */
class OwnerFulfillmentGuard implements GuardInterface {

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
   * Instantiates a OwnerFulfillmentGuard service.
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
    $to_state = $transition->getToState()->getId();
    $from_state = $this->getState($entity);

    return $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $entity, $from_state, $to_state);
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The owner entity.
   *
   * @return string
   *   The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(RdfInterface $entity) {
    return $entity->field_owner_state->first()->value;
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Guard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\workflow_state_permission\WorkflowStatePermission;

/**
 * Guard class for the transitions of the asset release entity.
 */
class AssetReleaseFulfillmentGuard implements GuardInterface {

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
   * Constructs an AssetReleaseFulfillmentGuard service.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermission $workflow_state_permission
   *   The workflow state permission service.
   */
  public function __construct(AccountInterface $current_user, WorkflowStatePermission $workflow_state_permission) {
    $this->currentUser = $current_user;
    $this->workflowStatePermission = $workflow_state_permission;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    /** @var \Drupal\joinup_workflow\EntityWorkflowStateInterface $entity */
    $from_state = $entity->getWorkflowState();
    $to_state = $transition->getToState()->getId();
    return $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $entity, $from_state, $to_state);
  }

}

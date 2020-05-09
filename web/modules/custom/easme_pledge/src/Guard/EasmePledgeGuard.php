<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;

/**
 * Guard class for the transitions of community content.
 */
class EasmePledgeGuard implements GuardInterface {

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The allowed transitions array.
   *
   * @var array
   */
  protected $transitions;

  /**
   * The permission scheme stored in configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $permissionScheme;

  /**
   * The workflow helper class.
   *
   * @var \Drupal\joinup_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * The workflow state permission service.
   *
   * @var \Drupal\workflow_state_permission\WorkflowStatePermissionInterface
   */
  protected $workflowStatePermission;

  /**
   * Constructs a new CommunityContentGuard service.
   *
   * The classes inheriting this class, should also ensure that they set the
   * protected variable $transitions to be used by the ::allowed() method.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflowStatePermission
   *   The workflow state permission service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountInterface $currentUser, WorkflowHelperInterface $workflow_helper, WorkflowStatePermissionInterface $workflowStatePermission) {
    $this->currentUser = $currentUser;
    $this->workflowHelper = $workflow_helper;
    $this->permissionScheme = $configFactory->get('joinup_community_content.permission_scheme');
    $this->workflowStatePermission = $workflowStatePermission;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    $from_state = $this->getState($entity);
    $to_state = $transition->getToState()->getId();
    return $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $entity, $from_state, $to_state);
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The discussion entity.
   *
   * @return string
   *   The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(EntityInterface $entity) {
    return $entity->get('field_state')->first()->value;
  }

}

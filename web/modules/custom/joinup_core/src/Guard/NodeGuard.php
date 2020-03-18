<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\joinup_core\WorkflowHelperInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;

/**
 * Guard class for the transitions of nodes.
 */
class NodeGuard implements GuardInterface {

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The relation manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

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
   * @var \Drupal\joinup_core\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * The workflow state permission service.
   *
   * @var \Drupal\workflow_state_permission\WorkflowStatePermissionInterface
   */
  protected $workflowStatePermission;

  /**
   * Instantiates the NodeGuard service.
   *
   * The classes inheriting this class, should also ensure that they set the
   * protected variable $transitions to be used by the ::allowed() method.
   *
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The relation manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflowStatePermission
   *   The workflow state permission service.
   */
  public function __construct(JoinupRelationManagerInterface $relationManager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser, WorkflowHelperInterface $workflow_helper, WorkflowStatePermissionInterface $workflowStatePermission) {
    $this->relationManager = $relationManager;
    $this->currentUser = $currentUser;
    $this->workflowHelper = $workflow_helper;
    $this->permissionScheme = $configFactory->get('joinup_community_content.permission_scheme');
    $this->workflowStatePermission = $workflowStatePermission;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if ($entity->isNew()) {
      return $this->allowedCreate($transition, $workflow, $entity);
    }
    else {
      return $this->allowedUpdate($transition, $workflow, $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function allowedCreate(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    $permission_scheme = $this->permissionScheme->get('create');
    $workflow_id = $workflow->getId();
    $content_creation = (string) $this->relationManager->getParentContentCreationOption($entity);

    if (!isset($permission_scheme[$workflow_id][$content_creation][$transition->getId()])) {
      return FALSE;
    }
    return $this->workflowHelper->userHasRoles($entity, $this->currentUser, $permission_scheme[$workflow_id][$content_creation][$transition->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function allowedUpdate(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
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

<?php

namespace Drupal\joinup_core\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\joinup_core\WorkflowHelperInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Guard class for the transitions of nodes.
 */
class NodeGuard implements GuardInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $ogMembershipManager;

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
   * Instantiates the NodeGuard service.
   *
   * The classes inheriting this class, should also ensure that they set the
   * protected variable $transitions to be used by the ::allowed() method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The relation manager service.
   * @param \Drupal\og\MembershipManagerInterface $ogMembershipManager
   *   The OG membership manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupRelationManagerInterface $relationManager, MembershipManagerInterface $ogMembershipManager, ConfigFactoryInterface $configFactory, AccountInterface $currentUser, WorkflowHelperInterface $workflow_helper) {
    $this->entityTypeManager = $entityTypeManager;
    $this->relationManager = $relationManager;
    $this->ogMembershipManager = $ogMembershipManager;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->workflowHelper = $workflow_helper;
    $this->permissionScheme = $configFactory->get('joinup_community_content.permission_scheme');
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
    $e_library = $this->relationManager->getParentElibrary($entity);

    if (!isset($permission_scheme[$workflow_id][$e_library][$transition->getId()])) {
      return FALSE;
    }
    return $this->workflowHelper->userHasRoles($entity, $this->currentUser, $permission_scheme[$workflow_id][$e_library][$transition->getId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function allowedUpdate(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    $permission_scheme = $this->permissionScheme->get('update');
    $access = FALSE;

    $workflow_id = $workflow->getId();
    if ($this->workflowHelper->userHasOwnAnyRoles($entity, $this->currentUser, $permission_scheme[$workflow_id][$transition->getId()])) {
      $access = TRUE;
    }

    // If the user has access to the 'request_deletion' transition but also has
    // delete permission to the entity, revoke the permission to request
    // deletion.
    if ($access && $transition->getId() === 'request_deletion') {
      $access = !$entity->access('delete');
    }

    return $access;
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

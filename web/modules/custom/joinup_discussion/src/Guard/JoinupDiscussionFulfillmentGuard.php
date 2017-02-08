<?php

namespace Drupal\joinup_discussion\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_discussion\JoinupDiscussionRelationManager;
use Drupal\joinup_user\WorkflowUserProvider;
use Drupal\og\MembershipManagerInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Class JoinupDiscussionFulfillmentGuard.
 */
class JoinupDiscussionFulfillmentGuard implements GuardInterface {

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
   * The discussions relation manager.
   *
   * @var \Drupal\joinup_discussion\JoinupDiscussionRelationManager
   */
  protected $relationManager;

  /**
   * The workflow user provider service.
   *
   * @var \Drupal\joinup_user\WorkflowUserProvider
   */
  protected $workflowUserProvider;

  /**
   * Instantiates the JoinupDiscussionFulfillmentGuard service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup_user\WorkflowUserProvider $workflow_user_provider
   *   The workflow user provider service.
   * @param \Drupal\joinup_discussion\JoinupDiscussionRelationManager $relation_manager
   *   The discussions relation service.
   * @param \Drupal\og\MembershipManagerInterface $og_membership_manager
   *   The OG membership manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkflowUserProvider $workflow_user_provider, JoinupDiscussionRelationManager $relation_manager, MembershipManagerInterface $og_membership_manager, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowUserProvider = $workflow_user_provider;
    $this->relationManager = $relation_manager;
    $this->ogMembershipManager = $og_membership_manager;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if ($this->workflowUserProvider->getUser()->hasPermission('bypass node access')) {
      return TRUE;
    }

    $allowed_conditions = $this->configFactory->get('joinup_discussion.settings')->get('transitions');

    // Check if the user has one of the allowed system roles.
    $from_state = $this->getState($entity);
    $transition_id = $transition->getId();
    $authorized_roles = isset($allowed_conditions[$transition_id][$from_state]) ? $allowed_conditions[$transition_id][$from_state] : [];
    $user = $this->workflowUserProvider->getUser();
    if (array_intersect($authorized_roles, $user->getRoles())) {
      return TRUE;
    }

    $parent = $this->relationManager->getDiscussionParent($entity);
    $membership = $this->ogMembershipManager->getMembership($parent, $user);
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The discussion entity.
   *
   * @return string
   *    The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(EntityInterface $entity) {
    return $entity->get('field_discussion_state')->first()->value;
  }

}

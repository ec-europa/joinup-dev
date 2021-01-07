<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Plugin\WorkflowStatePermission;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine_permissions\StateMachinePermissionStringConstructor;
use Drupal\workflow_state_permission\WorkflowStatePermissionPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * Depending on the user role some workflow states are not available. For
 * example only the author of a community content can request deletion, and only
 * a moderator or facilitator can archive a discussion.
 *
 * @WorkflowStatePermission(
 *   id = "community_content",
 * )
 */
class CommunityContentWorkflowStatePermission extends PluginBase implements WorkflowStatePermissionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The workflow helper service.
   *
   * @var \Drupal\joinup_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * Constructs a CollectionWorkflowStatePermissions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflowHelper
   *   The workflow helper service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, WorkflowHelperInterface $workflowHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->workflowHelper = $workflowHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('joinup_workflow.workflow_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity instanceof CommunityContentInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, WorkflowInterface $workflow, string $from_state, string $to_state): bool {
    if ($account->hasPermission($entity->getEntityType()->getAdminPermission())) {
      return TRUE;
    }

    $any_permission = StateMachinePermissionStringConstructor::constructTransitionPermission($entity->getEntityTypeId(), $entity->bundle(), $workflow, $from_state, $to_state, TRUE);
    $own_permission = StateMachinePermissionStringConstructor::constructTransitionPermission($entity->getEntityTypeId(), $entity->bundle(), $workflow, $from_state, $to_state, FALSE);
    $has_access = $account->hasPermission($any_permission) || (($entity->getOwnerId() === $account->id()) && $account->hasPermission($own_permission));
    if ($has_access) {
      return TRUE;
    }

    // No access has been given by the account permissions, check OG permissions
    // next.
    $group = $entity->getGroup();
    $has_access = $this->workflowHelper->hasOgPermission($any_permission, $group, $account)
      || ($entity->getOwnerId() === $account->id() && $this->workflowHelper->hasOgPermission($own_permission, $group, $account));

    // If the user has access to the 'request_deletion' transition but also has
    // delete permission to the entity, revoke the permission to request
    // deletion.
    if ($has_access && $to_state === 'deletion_request') {
      $has_access = !$entity->access('delete');
    }

    return $has_access;
  }

}

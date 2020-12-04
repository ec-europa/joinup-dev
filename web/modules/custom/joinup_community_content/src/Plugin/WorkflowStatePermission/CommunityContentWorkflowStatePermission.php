<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Plugin\WorkflowStatePermission;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\og\MembershipManagerInterface;
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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The OG membership manager.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The workflow helper service.
   *
   * @var \Drupal\joinup_workflow\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CollectionWorkflowStatePermissions object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The OG membership manager.
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflowHelper
   *   The workflow helper service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, MembershipManagerInterface $membershipManager, WorkflowHelperInterface $workflowHelper, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->membershipManager = $membershipManager;
    $this->workflowHelper = $workflowHelper;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('og.membership_manager'),
      $container->get('joinup_workflow.workflow_helper'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return CommunityContentHelper::isCommunityContent($entity);
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
    $access_result = $account->hasPermission($any_permission) || (($entity->getOwnerId() === $account->id()) && $account->hasPermission($own_permission));
    if ($access_result) {
      return AccessResult::allowed()->cachePerUser()->cachePerPermissions()->addCacheableDependency($entity)->isAllowed();
    }

    // No access has been given by the account permissions, check OG permissions
    // next.
    $group = $entity->getGroup();
    $access_result = $this->workflowHelper->hasOgPermission($any_permission, $group, $account)
      || ($entity->getOwnerId() === $account->id() && $this->workflowHelper->hasOgPermission($own_permission, $group, $account));

    // If the user has access to the 'request_deletion' transition but also has
    // delete permission to the entity, revoke the permission to request
    // deletion.
    if ($access_result && $to_state === 'deletion_request') {
      $access_result = !$entity->access('delete');
    }

    return $access_result;
  }

}

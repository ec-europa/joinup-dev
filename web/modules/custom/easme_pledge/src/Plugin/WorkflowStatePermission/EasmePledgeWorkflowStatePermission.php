<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge\Plugin\WorkflowStatePermission;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_community_content\CommunityContentWorkflowAccessControlHandler;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\workflow_state_permission\WorkflowStatePermissionPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * @WorkflowStatePermission(
 *   id = "easme_pledge",
 * )
 */
class EasmePledgeWorkflowStatePermission extends PluginBase implements WorkflowStatePermissionPluginInterface, ContainerFactoryPluginInterface {

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, MembershipManagerInterface $membershipManager, WorkflowHelperInterface $workflowHelper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->membershipManager = $membershipManager;
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
      $container->get('config.factory'),
      $container->get('og.membership_manager'),
      $container->get('joinup_workflow.workflow_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity->bundle() === 'pledge';
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool {
    $permission_scheme = $this->configFactory->get('easme_pledge.permission_scheme')->get('update');
    $access = FALSE;

    $workflow_id = $entity->{CommunityContentWorkflowAccessControlHandler::STATE_FIELD}->first()->getWorkflow()->getId();
    $matrix = $permission_scheme[$workflow_id][$to_state][$from_state] ?? NULL;
    if (!empty($matrix) && $this->workflowHelper->userHasOwnAnyRoles($entity, $account, $matrix)) {
      $access = TRUE;
    }

    return $access;
  }

}

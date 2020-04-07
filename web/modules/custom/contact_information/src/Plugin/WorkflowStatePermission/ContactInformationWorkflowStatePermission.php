<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Plugin\WorkflowStatePermission;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\contact_information\ContactInformationRelationInfoInterface;
use Drupal\joinup_workflow\WorkflowHelperInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Drupal\workflow_state_permission\WorkflowStatePermissionPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * @WorkflowStatePermission(
 *   id = "contact_information",
 * )
 *
 * @see: contact_information.settings.yml
 */
class ContactInformationWorkflowStatePermission extends PluginBase implements WorkflowStatePermissionPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The relation info service.
   *
   * @var \Drupal\contact_information\ContactInformationRelationInfo
   */
  protected $relationInfo;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The workflow helper class.
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
   * @param \Drupal\contact_information\ContactInformationRelationInfoInterface $relation_info
   *   The relation info service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The membership manager service.
   * @param \Drupal\joinup_workflow\WorkflowHelperInterface $workflow_helper
   *   The workflow helper class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, ContactInformationRelationInfoInterface $relation_info, MembershipManagerInterface $membership_manager, WorkflowHelperInterface $workflow_helper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->relationInfo = $relation_info;
    $this->membershipManager = $membership_manager;
    $this->workflowHelper = $workflow_helper;
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
      $container->get('contact_information.relation_info'),
      $container->get('og.membership_manager'),
      $container->get('joinup_workflow.workflow_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity->getEntityTypeId() === 'rdf_entity' && $entity->bundle() === 'contact_information';
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool {
    if ($account->hasPermission('administer rdf entity')) {
      return TRUE;
    }

    $allowed_conditions = $this->configFactory->get('contact_information.settings')->get('transitions');
    $matrix = $allowed_conditions[$to_state][$from_state];
    $access = !empty($matrix) && $this->userHasOwnAnyRoles($entity, $account, $matrix);

    // If the user has access to the 'request_deletion' transition but also has
    // delete permission to the entity, revoke the permission to request
    // deletion.
    if ($access && $to_state === 'deletion_request') {
      $access = !$entity->access('delete');
    }

    return $access;
  }

  /**
   * Checks if the user has any required roles globally or in the parents.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The contact information entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account object.
   * @param array $roles
   *   A list of roles keyed by 'any' and 'own' and with the type 'roles' or
   *   'og_roles'.
   *
   * @return bool
   *   The access result as boolean.
   */
  protected function userHasOwnAnyRoles(RdfInterface $entity, AccountInterface $account, array $roles): bool {
    $own = $entity->getOwnerId() === $account->id();

    if (isset($roles['any']['roles'])) {
      if (array_intersect($account->getRoles(), $roles['any']['roles'])) {
        return TRUE;
      }
    }

    if ($own && isset($roles['own']['roles'])) {
      if (array_intersect($account->getRoles(), $roles['own']['roles'])) {
        return TRUE;
      }
    }

    foreach ($this->relationInfo->getContactInformationRelatedGroups($entity) as $group) {
      $membership = $this->membershipManager->getMembership($group, $account->id());
      if (empty($membership)) {
        continue;
      }

      $role_ids = $membership->getRolesIds();
      if (isset($roles['any']['og_roles'])) {
        if (array_intersect($role_ids, $roles['any']['og_roles'])) {
          return TRUE;
        }
      }
      if ($own && isset($roles['own']['og_roles'])) {
        if (array_intersect($membership->getRolesIds(), $roles['own']['og_roles'])) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}

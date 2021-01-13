<?php

declare(strict_types = 1);

namespace Drupal\contact_information\Plugin\WorkflowStatePermission;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\contact_information\Entity\ContactInformationInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
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
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity instanceof ContactInformationInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, WorkflowInterface $workflow, string $from_state, string $to_state): bool {
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
   * @param \Drupal\contact_information\Entity\ContactInformationInterface $entity
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
  protected function userHasOwnAnyRoles(ContactInformationInterface $entity, AccountInterface $account, array $roles): bool {
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

    // When the user creates a group, they do not have any roles in the group
    // yet. There is no need to have a check for groups when the entity is new.
    if ($entity->isNew()) {
      return FALSE;
    }

    if ($group = $entity->getRelatedGroup()) {
      $membership = $group->getMembership((int) $account->id());
      if (empty($membership)) {
        return FALSE;
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

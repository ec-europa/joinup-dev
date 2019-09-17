<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\WorkflowStatePermission;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_core\WorkflowStatePermissionPluginInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * Depending on the user role some workflow states are not available. For
 * example if a collection is in the 'validated' state a facilitator can only
 * change the state to 'proposed' or 'draft', while a moderator can change to
 * any state.
 *
 * @WorkflowStatePermission(
 *   id = "collection",
 * )
 *
 * @see collection.settings.yml
 */
class CollectionWorkflowStatePermission extends PluginBase implements WorkflowStatePermissionPluginInterface, ContainerFactoryPluginInterface {

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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, MembershipManagerInterface $membershipManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->membershipManager = $membershipManager;
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
      $container->get('og.membership_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity->getEntityTypeId() === 'rdf_entity' && $entity->bundle() === 'collection';
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool {
    $allowed_conditions = $this->configFactory->get('collection.settings')->get('transitions');

    if ($account->hasPermission($entity->getEntityType()->getAdminPermission())) {
      return TRUE;
    }

    // Check if the user has one of the allowed system roles.
    $authorized_roles = isset($allowed_conditions[$to_state][$from_state]) ? $allowed_conditions[$to_state][$from_state] : [];
    if (array_intersect($authorized_roles, $account->getRoles())) {
      return TRUE;
    }

    // Check if the user has one of the allowed group roles.
    $membership = $this->membershipManager->getMembership($entity, $account->id());
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

}

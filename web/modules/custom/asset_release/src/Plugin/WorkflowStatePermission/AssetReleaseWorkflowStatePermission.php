<?php

declare(strict_types = 1);

namespace Drupal\asset_release\Plugin\WorkflowStatePermission;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\asset_release\AssetReleaseRelations;
use Drupal\og\MembershipManagerInterface;
use Drupal\workflow_state_permission\WorkflowStatePermissionPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * @WorkflowStatePermission(
 *   id = "asset_release",
 * )
 *
 * @see: asset_release.settings.yml
 */
class AssetReleaseWorkflowStatePermission extends PluginBase implements WorkflowStatePermissionPluginInterface, ContainerFactoryPluginInterface {

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
   * The asset release relation service.
   *
   * @var \Drupal\asset_release\AssetReleaseRelations
   */
  protected $assetReleaseRelationManager;

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
   * @param \Drupal\asset_release\AssetReleaseRelations $assetReleaseRelationManager
   *   The asset release relation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, MembershipManagerInterface $membershipManager, AssetReleaseRelations $assetReleaseRelationManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $configFactory;
    $this->membershipManager = $membershipManager;
    $this->assetReleaseRelationManager = $assetReleaseRelationManager;
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
      $container->get('asset_release.relations')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity->getEntityTypeId() === 'rdf_entity' && $entity->bundle() === 'asset_release';
  }

  /**
   * {@inheritdoc}
   */
  public function isStateUpdatePermitted(AccountInterface $account, EntityInterface $entity, string $from_state, string $to_state): bool {
    $allowed_conditions = $this->configFactory->get('asset_release.settings')->get('transitions');
    if ($account->hasPermission('bypass node access')) {
      return TRUE;
    }

    // Check if the user has one of the allowed system roles.
    $authorized_roles = isset($allowed_conditions[$to_state][$from_state]) ? $allowed_conditions[$to_state][$from_state] : [];
    if (array_intersect($authorized_roles, $account->getRoles())) {
      return TRUE;
    }

    // Check if the user has one of the allowed group roles.
    $parent = $this->assetReleaseRelationManager->getReleaseSolution($entity);
    $membership = $this->membershipManager->getMembership($parent, $account->id());
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

}

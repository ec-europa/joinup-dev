<?php

namespace Drupal\asset_release\Guard;

use Drupal\asset_release\AssetReleaseRelations;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_core\WorkflowUserProvider;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Guard class for the transitions of the asset release entity.
 *
 * @package Drupal\asset_release\Guard
 */
class AssetReleaseFulfillmentGuard implements GuardInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Holds the workflow user object needed for the checks.
   *
   * Will be used to override the default user used by workflows.
   *
   * @var \Drupal\joinup_core\WorkflowUserProvider
   */
  protected $workflowUserProvider;

  /**
   * The asset release relation manager.
   *
   * @var \Drupal\asset_release\AssetReleaseRelations
   */
  protected $assetReleaseRelationManager;

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
   * Constructs an AssetReleaseFulfillmentGuard service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup_core\WorkflowUserProvider $workflow_user_provider
   *   The workflow user provider service.
   * @param \Drupal\asset_release\AssetReleaseRelations $asset_release_relations
   *   The asset release relation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current logged in user.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkflowUserProvider $workflow_user_provider, AssetReleaseRelations $asset_release_relations, ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowUserProvider = $workflow_user_provider;
    $this->assetReleaseRelationManager = $asset_release_relations;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    $from_state = $this->getState($entity);

    // Allowed transitions are already filtered so we only need to check
    // for the transitions defined in the settings if they include a role the
    // user has.
    // @see: asset_release.settings.yml
    $allowed_conditions = $this->configFactory->get('asset_release.settings')->get('transitions');
    if ($this->currentUser->hasPermission('bypass node access')) {
      return TRUE;
    }

    // Check if the user has one of the allowed system roles.
    $authorized_roles = isset($allowed_conditions[$transition->getId()][$from_state]) ? $allowed_conditions[$transition->getId()][$from_state] : [];
    $user = $this->workflowUserProvider->getUser();
    if (array_intersect($authorized_roles, $user->getRoles())) {
      return TRUE;
    }

    // Check if the user has one of the allowed group roles.
    $parent = $this->assetReleaseRelationManager->getReleaseSolution($entity);
    $membership = Og::getMembership($parent, $user);
    return $membership && array_intersect($authorized_roles, $membership->getRolesIds());
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The asset release entity.
   *
   * @return string
   *   The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(RdfInterface $entity) {
    return $entity->field_isr_state->first()->value;
  }

}

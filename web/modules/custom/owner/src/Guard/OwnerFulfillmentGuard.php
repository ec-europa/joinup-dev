<?php

namespace Drupal\owner\Guard;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_core\WorkflowUserProvider;
use Drupal\rdf_entity\RdfInterface;
use Drupal\state_machine\Guard\GuardInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Guard class for the transitions of the owner entity.
 *
 * @package Drupal\owner\Guard
 */
class OwnerFulfillmentGuard implements GuardInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Instantiates a OwnerFulfillmentGuard service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup_core\WorkflowUserProvider $workflow_user_provider
   *   The workflow user provider service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, WorkflowUserProvider $workflow_user_provider, ConfigFactoryInterface $config_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->workflowUserProvider = $workflow_user_provider;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function allowed(WorkflowTransition $transition, WorkflowInterface $workflow, EntityInterface $entity) {
    if ($this->workflowUserProvider->getUser()->hasPermission('administer rdf entity')) {
      return TRUE;
    }

    $from_state = $this->getState($entity);

    // Allowed transitions are already filtered so we only need to check
    // for the transitions defined in the settings if they include a role the
    // user has.
    // @see: owner.settings.yml
    $allowed_conditions = $this->configFactory->get('owner.settings')->get('transitions');

    // Check if the user has one of the allowed system roles.
    $authorized_roles = isset($allowed_conditions[$transition->getId()][$from_state]) ? $allowed_conditions[$transition->getId()][$from_state] : [];
    $user = $this->workflowUserProvider->getUser();
    return (bool) array_intersect($authorized_roles, $user->getRoles());
  }

  /**
   * Retrieve the initial state value of the entity.
   *
   * @param \Drupal\rdf_entity\RdfInterface $entity
   *   The owner entity.
   *
   * @return string
   *   The machine name value of the state.
   *
   * @see https://www.drupal.org/node/2745673
   */
  protected function getState(RdfInterface $entity) {
    return $entity->field_owner_state->first()->value;
  }

}

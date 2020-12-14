<?php

declare(strict_types = 1);

namespace Drupal\state_machine_permissions\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
use Drupal\state_machine_permissions\StateMachinePermissionStringConstructor;
use Drupal\state_machine_permissions\StateMachinePermissionsHelperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides permissions related to og and state machine.
 */
class StateMachineOgSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The permissions helper service.
   *
   * @var \Drupal\state_machine_permissions\StateMachinePermissionsHelperInterface
   */
  protected $permissionsHelper;

  /**
   * Creates a StateMachineOgSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\state_machine_permissions\StateMachinePermissionsHelperInterface $permissions_helper
   *   The permissions helper service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, StateMachinePermissionsHelperInterface $permissions_helper) {
    $this->entityFieldManager = $entity_field_manager;
    $this->permissionsHelper = $permissions_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OgPermissionEventInterface::EVENT_NAME => [
        ['ogGroupStateMachinePermissions'],
        ['ogGroupContentStateMachineUpdatePermissions'],
      ],
    ];
  }

  /**
   * Declares state machine permissions for the group itself.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function ogGroupStateMachinePermissions(OgPermissionEventInterface $event): void {
    // Check if the group has a state field.
    $workflows = $this->permissionsHelper->getPossibleWorkflowsForBundle($event->getGroupEntityTypeId(), $event->getGroupBundleId());
    $permissions = [];
    foreach ($workflows as $workflow) {
      $transitions = $workflow->getTransitions();
      foreach ($transitions as $transition) {
        $to_state = $transition->getToState();
        foreach ($transition->getFromStates() as $from_state) {
          $permission_string = StateMachinePermissionStringConstructor::constructGroupStateUpdatePermission($workflow, $from_state->getId(), $to_state->getId());
          $permissions[$permission_string] = new GroupPermission([
            'name' => $permission_string,
            'title' => $this->t('@workflow_label (:workflow_id): Transition from @from_state to @to_state', [
              '@workflow_label' => $workflow->getLabel(),
              ':workflow_id' => $workflow->getId(),
              '@from_state' => $from_state->getLabel(),
              '@to_state' => $to_state->getLabel(),
            ]),
          ]);
        }
      }
    }
    $event->setPermissions($permissions);
  }

  /**
   * Generate group content state update permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function ogGroupContentStateMachineUpdatePermissions(OgPermissionEventInterface $event): void {
    foreach ($event->getGroupContentBundleIds() as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle) {
        $workflows = $this->permissionsHelper->getPossibleWorkflowsForBundle($entity_type_id, $bundle, []);
        $permissions = [];
        foreach ($workflows as $workflow) {
          $transitions = $workflow->getTransitions();
          foreach ($transitions as $transition) {
            $to_state = $transition->getToState();
            foreach (['own' => FALSE, 'any' => TRUE] as $key => $permission) {
              // Create a fake transition permission to consistently manage all
              // changes to the entity since it is not supported in
              // state_machine.
              // @todo Replace this with dedicated permissions.
              // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6316
              $permission_string = StateMachinePermissionStringConstructor::constructTransitionPermission($entity_type_id, $bundle, $workflow, $to_state->getId(), $to_state->getId(), $permission);
              $permissions[$permission_string] = new GroupContentOperationPermission([
                'name' => $permission_string,
                'title' => $this->t('@workflow_label (:workflow_id): Transition from @from_state to @to_state - :key :bundle :entity_type_id entity', [
                  '@workflow_label' => $workflow->getLabel(),
                  ':workflow_id' => $workflow->getId(),
                  '@from_state' => $to_state->getLabel(),
                  '@to_state' => $to_state->getLabel(),
                  ':key' => $key,
                  ':bundle' => $bundle,
                  ':entity_type_id' => $entity_type_id,
                ]),
                'entityType' => $entity_type_id,
                'bundle' => $bundle,
                'operation' => $permission_string,
                'owner' => $key === 'own',
              ]);

              foreach ($transition->getFromStates() as $from_state) {
                $permission_string = StateMachinePermissionStringConstructor::constructTransitionPermission($entity_type_id, $bundle, $workflow, $from_state->getId(), $to_state->getId(), $permission);
                $permissions[$permission_string] = new GroupContentOperationPermission([
                  'name' => $permission_string,
                  'title' => $this->t('@workflow_label (:workflow_id): Transition from @from_state to @to_state - :key :bundle :entity_type_id entity', [
                    '@workflow_label' => $workflow->getLabel(),
                    ':workflow_id' => $workflow->getId(),
                    '@from_state' => $from_state->getLabel(),
                    '@to_state' => $to_state->getLabel(),
                    ':key' => $key,
                    ':bundle' => $bundle,
                    ':entity_type_id' => $entity_type_id,
                  ]),
                  'entityType' => $entity_type_id,
                  'bundle' => $bundle,
                  'operation' => $permission_string,
                  'owner' => $key === 'own',
                ]);
              }
            }
          }
        }
        $event->setPermissions($permissions);
      }
    }
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\state_machine_permissions\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\ContentCreationOptions;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
use Drupal\state_machine_permissions\StateMachinePermissionsHelperInterface;
use Drupal\state_machine_permissions\StateMachinePermissionStringConstructor;
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
        ['ogGroupContentStateMachineCreatePermissions'],
        ['ogGroupContentStateMachineViewPermissions'],
        ['ogGroupContentStateMachineUpdatePermissions'],
        ['ogGroupContentStateMachineDeletePermissions'],
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
   * Generate group content state create permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function ogGroupContentStateMachineCreatePermissions(OgPermissionEventInterface $event): void {
    $content_creation_options = [
      ContentCreationOptions::REGISTERED_USERS => 'any user',
      ContentCreationOptions::MEMBERS => 'only members',
      ContentCreationOptions::FACILITATORS => 'only facilitator',
    ];

    foreach ($event->getGroupContentBundleIds() as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle) {
        $workflows = $this->permissionsHelper->getPossibleWorkflowsForBundle($entity_type_id, $bundle, []);
        $permissions = [];
        foreach ($workflows as $workflow) {
          foreach ($workflow->getStates() as $state) {
            foreach ($content_creation_options as $key => $description) {
              $permission_string = StateMachinePermissionStringConstructor::constructGroupContentStateCreatePermission($entity_type_id, $bundle, $workflow, $state->getId(), $key);
              $permissions[$permission_string] = new GroupContentOperationPermission([
                'name' => $permission_string,
                'title' => $this->t('@workflow_label (:workflow_id): Create :bundle :entity_type_id in :state state when :creation_option can create content', [
                  '@workflow_label' => $workflow->getLabel(),
                  ':workflow_id' => $workflow->getId(),
                  ':bundle' => $bundle,
                  ':entity_type_id' => $entity_type_id,
                  ':state' => lcfirst($state->getLabel()),
                  ':creation_option' => $description,
                ]),
                'entity type' => $entity_type_id,
                'bundle' => $bundle,
                'operation' => $permission_string,
              ]);
            }
          }
        }
        $event->setPermissions($permissions);
      }
    }
  }

  /**
   * Generate group content state view permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function ogGroupContentStateMachineViewPermissions(OgPermissionEventInterface $event): void {
    foreach ($event->getGroupContentBundleIds() as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle) {
        $workflows = $this->permissionsHelper->getPossibleWorkflowsForBundle($entity_type_id, $bundle, []);
        $permissions = [];
        foreach ($workflows as $workflow) {
          foreach ($workflow->getStates() as $state) {
            foreach (['own' => FALSE, 'any' => TRUE] as $key => $permission) {
              $permission_string = StateMachinePermissionStringConstructor::constructGroupContentStateViewPermission($entity_type_id, $bundle, $workflow, $state->getId(), $permission);
              $permissions[$permission_string] = new GroupContentOperationPermission([
                'name' => $permission_string,
                'title' => $this->t('@workflow_label (:workflow_id): View :key :bundle :entity_type_id in :state state', [
                  '@workflow_label' => $workflow->getLabel(),
                  ':workflow_id' => $workflow->getId(),
                  ':key' => $key,
                  ':bundle' => $bundle,
                  ':entity_type_id' => $entity_type_id,
                  ':state' => $state->getLabel(),
                ]),
                'entity type' => $entity_type_id,
                'bundle' => $bundle,
                'operation' => $permission_string,
              ]);
            }
          }
        }
        $event->setPermissions($permissions);
      }
    }
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
            foreach ($transition->getFromStates() as $from_state) {
              foreach (['own' => FALSE, 'any' => TRUE] as $key => $permission) {
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
                  'entity type' => $entity_type_id,
                  'bundle' => $bundle,
                  'operation' => $permission_string,
                ]);
              }
            }
          }
        }
        $event->setPermissions($permissions);
      }
    }
  }

  /**
   * Generate group content state delete permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function ogGroupContentStateMachineDeletePermissions(OgPermissionEventInterface $event): void {
    foreach ($event->getGroupContentBundleIds() as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle) {
        $workflows = $this->permissionsHelper->getPossibleWorkflowsForBundle($entity_type_id, $bundle, []);
        $permissions = [];
        foreach ($workflows as $workflow) {
          foreach ($workflow->getStates() as $state) {
            foreach (['own' => FALSE, 'any' => TRUE] as $key => $permission) {
              $permission_string = StateMachinePermissionStringConstructor::constructGroupContentStateDeletePermission($entity_type_id, $bundle, $workflow, $state->getId(), $permission);
              $permissions[$permission_string] = new GroupContentOperationPermission([
                'name' => $permission_string,
                'title' => $this->t('@workflow_label (:workflow_id): Delete :key :bundle :entity_type_id in :state state', [
                  '@workflow_label' => $workflow->getLabel(),
                  ':workflow_id' => $workflow->getId(),
                  ':key' => $key,
                  ':bundle' => $bundle,
                  ':entity_type_id' => $entity_type_id,
                  ':state' => $state->getLabel(),
                ]),
                'entity type' => $entity_type_id,
                'bundle' => $bundle,
                'operation' => $permission_string,
              ]);
            }
          }
        }
        $event->setPermissions($permissions);
      }
    }
  }

}

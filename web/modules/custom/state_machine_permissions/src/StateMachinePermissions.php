<?php

namespace Drupal\state_machine_permissions;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Dynamic permissions provider.
 */
class StateMachinePermissions {

  use StringTranslationTrait;

  /**
   * The permission helper service.
   *
   * @var \Drupal\state_machine_permissions\StateMachinePermissionsHelperInterface
   */
  protected $permissionsHelper;

  /**
   * Constructs a state machine permissions object.
   */
  public function __construct() {
    $this->permissionsHelper = \Drupal::service('state_machine_permissions.helper');
  }

  /**
   * Returns an array of permissions related to state machine.
   *
   * @return array
   *   Dynamically generated permissions.
   */
  public function stateMachinePermissions(): array {
    $permissions = [];
    $state_field_map = $this->permissionsHelper->getStateFieldMap();
    if (empty($state_field_map)) {
      return $permissions;
    }

    foreach ($state_field_map as $entity_type_id => $bundles) {
      foreach ($bundles as $bundle => $fields) {
        $permissions += $this->generateWorkflowPermissionsForBundle($entity_type_id, $bundle, $fields);
      }
    }
    return $permissions;
  }

  /**
   * Returns an array of permissions per transmission for the given bundle.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The entity bundle.
   * @param array $field_names
   *   An array of state field names.
   *
   * @return array
   *   An array of permissions keyed by the permission id where each entry
   *   contains the 'label' key and the description.
   */
  public function generateWorkflowPermissionsForBundle($entity_type_id, $bundle, array $field_names) {
    $workflows = $this->permissionsHelper->getPossibleWorkflowsForBundle($entity_type_id, $bundle, $field_names);

    $permissions = [];
    foreach ($workflows as $workflow) {
      $transitions = $workflow->getTransitions();
      foreach ($transitions as $transition) {
        $to_state = $transition->getToState();
        foreach ($transition->getFromStates() as $from_state) {
          foreach (['own' => FALSE, 'any' => TRUE] as $key => $permission) {
            $permissions[StateMachinePermissionStringConstructor::constructTransitionPermission($entity_type_id, $bundle, $workflow, $from_state->getId(), $to_state->getId(), $permission)] = [
              'title' => $this->t('@workflow_label (:workflow_id): Transition from @from_state to @to_state - :key :bundle :entity_type_id entity', [
                '@workflow_label' => $workflow->getLabel(),
                ':workflow_id' => $workflow->getId(),
                '@from_state' => $from_state->getLabel(),
                '@to_state' => $to_state->getLabel(),
                ':key' => $key,
                ':bundle' => $bundle,
                ':entity_type_id' => $entity_type_id,
              ]),
            ];
          }
        }
      }
    }
    return $permissions;
  }

}

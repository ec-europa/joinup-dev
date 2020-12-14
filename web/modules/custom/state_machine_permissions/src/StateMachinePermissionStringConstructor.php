<?php

declare(strict_types = 1);

namespace Drupal\state_machine_permissions;

use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;

/**
 * Handles generation of permission strings.
 */
class StateMachinePermissionStringConstructor {

  /**
   * Constructs a transition permission.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle ID.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow.
   * @param string $from_state_id
   *   The from state.
   * @param string $to_state_id
   *   The to state.
   * @param bool $any
   *   Whether to return the permission for owned entities or any.
   *
   * @return string
   *   The permission string.
   */
  public static function constructTransitionPermission(string $entity_type_id, string $bundle, WorkflowInterface $workflow, string $from_state_id, string $to_state_id, bool $any): string {
    return $any === TRUE ?
      "transition any {$entity_type_id} {$bundle} from {$from_state_id} to {$to_state_id} using the {$workflow->getGroup()} - {$workflow->getId()} workflow" :
      "transition own {$entity_type_id} {$bundle} from {$from_state_id} to {$to_state_id} using the {$workflow->getGroup()} - {$workflow->getId()} workflow";
  }

  /**
   * Constructs a state update permission for an OG group.
   *
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow plugin.
   * @param string $from_state_id
   *   The from state.
   * @param string $to_state_id
   *   The to state.
   *
   * @return string
   *   The permission string.
   */
  public static function constructGroupStateUpdatePermission(WorkflowInterface $workflow, string $from_state_id, string $to_state_id): string {
    return "transition group from {$from_state_id} to {$to_state_id} using the {$workflow->getGroup()} - {$workflow->getId()} workflow";
  }

}

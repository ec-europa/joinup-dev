<?php

declare(strict_types = 1);

namespace Drupal\state_machine_permissions;

use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;

/**
 * Handles generation of permission strings.
 */
class StateMachinePermissionStringConstructor {

  /**
   * Constructs the create permission for a workflow state.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow.
   * @param string $state
   *   The workflow state id.
   * @param string $creation_option
   *   The creation option of the group. Either 'any_user' or 'only_members' or
   *   'only_facilitators'.
   *
   * @return string
   *   The permission string.
   */
  public static function constructGroupContentStateCreatePermission(string $entity_type_id, string $bundle, WorkflowInterface $workflow, string $state, string $creation_option): string {
    return "{$workflow->getGroup()} - {$workflow->getId()} - create {$bundle} {$entity_type_id} in {$state} state when {$creation_option} can create content";
  }

  /**
   * Constructs the view permission for a workflow state.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow.
   * @param string $state
   *   The workflow state id.
   * @param bool $any
   *   Whether to return the permission for owned entities or any.
   *
   * @return string
   *   The permission string.
   */
  public static function constructGroupContentStateViewPermission(string $entity_type_id, string $bundle, WorkflowInterface $workflow, string $state, bool $any): string {
    $any = $any === TRUE ? 'any' : 'own';
    return "{$workflow->getGroup()} - {$workflow->getId()} - view {$any} {$bundle} {$entity_type_id} in {$state} state";
  }

  /**
   * Constructs a transition permission.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
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
      "{$workflow->getGroup()} - {$workflow->getId()} - {$entity_type_id} - any {$bundle} entity  - transition from {$from_state_id} to {$to_state_id}" :
      "{$workflow->getGroup()} - {$workflow->getId()} - {$entity_type_id} - own {$bundle} entity  - transition from {$from_state_id} to {$to_state_id}";
  }

  /**
   * Constructs the delete permission for a workflow state.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow.
   * @param string $state
   *   The workflow state id.
   * @param bool $any
   *   Whether to return the permission for owned entities or any.
   *
   * @return string
   *   The permission string.
   */
  public static function constructGroupContentStateDeletePermission(string $entity_type_id, string $bundle, WorkflowInterface $workflow, string $state, bool $any): string {
    $any = $any === TRUE ? 'any' : 'own';
    return "{$workflow->getGroup()} - {$workflow->getId()} - delete {$any} {$bundle} {$entity_type_id} in {$state} state";
  }

  /**
   * Constructs a state permission for an og group.
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
    return "{$workflow->getGroup()} - {$workflow->getId()} - transition from {$from_state_id} to {$to_state_id}";
  }

}

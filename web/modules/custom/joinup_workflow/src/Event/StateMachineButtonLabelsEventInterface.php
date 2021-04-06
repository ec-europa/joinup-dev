<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow\Event;

use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Interface for events that allow to manipulate state machine button labels.
 */
interface StateMachineButtonLabelsEventInterface {

  /**
   * The event name.
   */
  const EVENT_NAME = 'joinup_workflow.state_machine_button_labels';

  /**
   * Returns the entity for which the workflow state buttons are labeled.
   *
   * @return \Drupal\joinup_workflow\EntityWorkflowStateInterface
   *   The entity.
   */
  public function getEntity(): EntityWorkflowStateInterface;

  /**
   * Returns the workflow transitions that are allowed for the entity.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\WorkflowTransition[]
   *   The transitions.
   */
  public function getTransitions(): array;

  /**
   * Returns the workflow transition with the given ID.
   *
   * @param string $id
   *   The ID of the transition to return.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\WorkflowTransition|null
   *   The transition, or NULL if the requested transition does not exist or is
   *   not allowed for the current user in the current workflow state.
   */
  public function getTransition(string $id): ?WorkflowTransition;

  /**
   * Returns the current workflow state ID.
   *
   * @return string
   *   The current workflow state ID.
   */
  public function getStateId(): string;

  /**
   * Updates the label of the transition with the given ID.
   *
   * @param string $id
   *   The ID of the transition for which to update the label.
   * @param string $label
   *   The updated label.
   *
   * @return $this
   *   The event, for chaining.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the transition with the given ID does not exist.
   */
  public function updateLabel(string $id, string $label): self;

}

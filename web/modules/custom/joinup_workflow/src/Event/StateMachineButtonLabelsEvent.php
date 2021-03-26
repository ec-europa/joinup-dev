<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow\Event;

use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired when labels for state machine buttons are being applied.
 *
 * Subscribe to this if you want to change the label of a workflow state
 * transition button under certain circumstances. These buttons are shown in the
 * edit forms of content which is subject to workflow.
 *
 * @see \Drupal\joinup_workflow\Plugin\Field\FieldWidget\StateMachineButtons
 */
class StateMachineButtonLabelsEvent extends Event implements StateMachineButtonLabelsEventInterface {

  /**
   * The entity having its state machine buttons relabeled.
   *
   * @var \Drupal\joinup_workflow\EntityWorkflowStateInterface
   */
  protected $entity;

  /**
   * The allowed transitions for the entity in its current workflow state.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\WorkflowTransition[]
   */
  protected $transitions;

  /**
   * The current workflow state.
   *
   * @var string
   */
  protected $stateId;

  /**
   * Constructs a new StateMachineButtonLabelsEvent.
   *
   * @param \Drupal\joinup_workflow\EntityWorkflowStateInterface $entity
   *   The entity that is being edited and wants to know which labels to use for
   *   their workflow transition buttons.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowTransition[] $transitions
   *   The workflow transitions which are allowed for the current user in the
   *   current workflow state of the entity.
   * @param string $state_id
   *   The current workflow state.
   */
  public function __construct(EntityWorkflowStateInterface $entity, array $transitions, string $state_id) {
    $this->entity = $entity;
    $this->transitions = $transitions;
    $this->stateId = $state_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): EntityWorkflowStateInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransitions(): array {
    return $this->transitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateId(): string {
    return $this->stateId;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransition(string $id): ?WorkflowTransition {
    return $this->transitions[$id] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLabel(string $id, string $label): StateMachineButtonLabelsEventInterface {
    if (!array_key_exists($id, $this->transitions)) {
      throw new \InvalidArgumentException("The transition with ID '$id' does not exist or is not allowed for the current user and workflow state.");
    }

    // The workflow object doesn't have a setter for the label, so we have to
    // replace it with a newly instantiated object and copy existing data.
    $original = $this->transitions[$id];
    $this->transitions[$id] = new WorkflowTransition(
      $original->getId(),
      $label,
      $original->getFromStates(),
      $original->getToState()
    );

    return $this;
  }

}

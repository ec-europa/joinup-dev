<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Reusable methods for entities that have a workflow state field.
 */
trait EntityWorkflowStateTrait {

  /**
   * {@inheritdoc}
   */
  public function getWorkflowState(): string {
    assert(method_exists($this, 'getMainPropertyValue'), __TRAIT__ . ' depends on JoinupBundleClassFieldAccessTrait. Please include it in your class.');
    assert(method_exists($this, 'getWorkflowStateFieldName'), __TRAIT__ . ' depends on EntityWorkflowStateInterface. Please implement it in your class.');
    $value = $this->getMainPropertyValue($this->getWorkflowStateFieldName());
    return $value ? (string) $value : '__new__';
  }

  /**
   * {@inheritdoc}
   */
  public function setWorkflowState(string $state): EntityWorkflowStateInterface {
    $this->getWorkflowStateField()->setValue($state);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasWorkflow(): bool {
    return $this->getWorkflowStateField()->getWorkflow() instanceof WorkflowInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow(): WorkflowInterface {
    $workflow = $this->getWorkflowStateField()->getWorkflow();
    if (!$workflow instanceof WorkflowInterface) {
      throw new \UnexpectedValueException(sprintf('No workflow object returned for entity of type %s with ID %s.', $this->getEntityTypeId(), (string) $this->id()));
    }
    return $workflow;
  }

  /**
   * Returns the workflow state field item for this entity.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The workflow state field item.
   */
  public function getWorkflowStateField(): StateItemInterface {
    assert(method_exists($this, 'getWorkflowStateFieldName'), __TRAIT__ . ' depends on EntityWorkflowStateInterface. Please implement it in your class.');
    return $this->get($this->getWorkflowStateFieldName())->first();
  }

  /**
   * {@inheritdoc}
   */
  public function isTargetWorkflowStateAllowed(string $to_state, ?string $from_state = NULL): bool {
    // Default the "from state" to the current workflow state.
    $from_state = $from_state ?? $this->getWorkflowState();

    // If the "from" and "to" state are different, this state change is governed
    // by a transition. If no transition exists then the update is not allowed.
    if ($from_state !== $to_state) {
      $allowed_transitions = array_filter($this->getWorkflow()->getAllowedTransitions($from_state, $this), function (WorkflowTransition $transition) use ($to_state) {
        return $transition->getToState() === $to_state;
      });
      return !empty($allowed_transitions);
    }

    // In the case of the entity being updated while not changing the workflow
    // state we can leverage the workflow state permission service.
    /** @var \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflow_state_permission */
    $workflow_state_permission = \Drupal::service('workflow_state_permission.workflow_state_permission');
    return $workflow_state_permission->isStateUpdatePermitted(\Drupal::currentUser(), $this, $this->getWorkflow(), $from_state, $to_state);
  }

}

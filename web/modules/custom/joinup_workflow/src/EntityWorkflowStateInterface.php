<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;

/**
 * Interface for bundle classes that are subject to workflows.
 */
interface EntityWorkflowStateInterface {

  /**
   * Returns the current workflow state.
   *
   * @return string
   *   The workflow state.
   */
  public function getWorkflowState(): string;

  /**
   * Sets the workflow state.
   *
   * @param string $state
   *   The machine readable workflow state value to set.
   *
   * @return \Drupal\joinup_workflow\EntityWorkflowStateInterface
   *   The object for chaining.
   *
   * @todo Once we are on PHP 7.4, leverage return type covariance by replacing
   *   the return type with `self`. This will ensure the entity retains its type
   *   after chaining.
   * @see https://3v4l.org/e3aT1
   */
  public function setWorkflowState(string $state): EntityWorkflowStateInterface;

  /**
   * Returns whether or not a workflow object is associated with the entity.
   *
   * Normally calling code can rely on a workflow object being available, except
   * if the entity is orphaned. Call this method if your code is running as part
   * of the orphan cleanup (e.g. in a entity delete hook).
   *
   * @return bool
   *   TRUE if a workflow state is set, FALSE if not.
   */
  public function hasWorkflow(): bool;

  /**
   * Returns the workflow object.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\WorkflowInterface
   *   The workflow object.
   *
   * @throws \UnexpectedValueException
   *   Thrown if the workflow object cannot be instantiated because an invalid
   *   workflow ID has been set on the field. During normal usage this will only
   *   occur during the cleanup of orphaned group content which is triggered
   *   after a group is deleted. If your code is running as part of the orphan
   *   cleanup (e.g. in a entity delete hook) then it is recommended to call
   *   ::hasWorkflow() first.
   */
  public function getWorkflow(): WorkflowInterface;

  /**
   * Returns the workflow state field item for this entity.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The workflow state field item.
   */
  public function getWorkflowStateField(): StateItemInterface;

  /**
   * Returns the machine name of the workflow state field.
   *
   * @return string
   *   The machine name.
   */
  public function getWorkflowStateFieldName(): string;

}

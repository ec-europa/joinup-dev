<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

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
   * Returns the workflow object.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\WorkflowInterface
   *   The workflow object.
   *
   * @throws \UnexpectedValueException
   *   Thrown if the workflow object cannot be instantiated because an invalid
   *   workflow ID has been set on the field. This is not expected to occur in
   *   normal usage. This is thrown to ensure that we have a log entry if this
   *   case occurs under unusual circumstances (e.g. data corruption).
   */
  public function getWorkflow(): WorkflowInterface;

  /**
   * Returns the machine name of the workflow state field.
   *
   * @return string
   *   The machine name.
   */
  public function getWorkflowStateFieldName(): string;

}

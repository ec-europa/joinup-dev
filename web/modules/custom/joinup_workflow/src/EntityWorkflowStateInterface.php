<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

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
   * Returns the machine name of the workflow state field.
   *
   * @return string
   *   The machine name.
   */
  public function getWorkflowStateFieldName(): string;

}

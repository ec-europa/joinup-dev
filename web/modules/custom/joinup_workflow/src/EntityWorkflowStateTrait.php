<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

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

}

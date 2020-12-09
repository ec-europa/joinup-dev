<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;

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
    $this->getWorkflowField()->setValue($state);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow(): WorkflowInterface {
    $workflow = $this->getWorkflowField()->getWorkflow();
    if (!$workflow instanceof WorkflowInterface) {
      throw new \UnexpectedValueException(sprintf('No workflow object returned for entity of type %s with ID %s.', $this->getEntityTypeId(), (string) $this->id()));
    }
    return $workflow;
  }

  /**
   * Returns the workflow field item fot this entity.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The workflow field item.
   */
  protected function getWorkflowField(): StateItemInterface {
    assert(method_exists($this, 'getWorkflowStateFieldName'), __TRAIT__ . ' depends on EntityWorkflowStateInterface. Please implement it in your class.');
    return $this->get($this->getWorkflowStateFieldName());
  }

}

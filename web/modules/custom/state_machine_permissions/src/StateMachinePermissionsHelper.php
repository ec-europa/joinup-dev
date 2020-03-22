<?php

declare(strict_types = 1);

namespace Drupal\state_machine_permissions;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\state_machine\WorkflowGroupManagerInterface;
use Drupal\state_machine\WorkflowManagerInterface;

/**
 * Provides helper methods for discovering fields, workflows and permissions.
 */
class StateMachinePermissionsHelper implements StateMachinePermissionsHelperInterface {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The workflow group manager service.
   *
   * @var \Drupal\state_machine\WorkflowGroupManagerInterface
   */
  protected $workflowGroupManager;

  /**
   * The workflow manager service.
   *
   * @var \Drupal\state_machine\WorkflowManagerInterface
   */
  protected $workflowManager;

  /**
   * Constructs a StateMachinePermissionsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\state_machine\WorkflowGroupManagerInterface $workflow_group_manager
   *   The workflow group manager service.
   * @param \Drupal\state_machine\WorkflowManagerInterface $workflow_manager
   *   The workflow manager service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, WorkflowGroupManagerInterface $workflow_group_manager, WorkflowManagerInterface $workflow_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->workflowGroupManager = $workflow_group_manager;
    $this->workflowManager = $workflow_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateFieldMap(): array {
    $field_definitions = $this->entityFieldManager->getFieldMapByFieldType('state');
    $return = [];
    foreach ($field_definitions as $entity_type_id => $field_states) {
      foreach ($field_states as $field_name => $field_state_data) {
        foreach ($field_state_data['bundles'] as $bundle) {
          $return[$entity_type_id][$bundle][$field_name] = $field_name;
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleWorkflowsForBundle(string $entity_type_id, string $bundle, array $field_names = []): array {
    $workflows = [];
    $field_definitions = $this->getEntityStateFieldDefinitions($entity_type_id, $bundle, $field_names);
    if (empty($field_definitions)) {
      return $workflows;
    }

    foreach ($field_definitions as $field_definition) {
      if (!empty($field_definition->getSetting('workflow_callback'))) {
        // If the 'workflow_callback' setting is set, the workflow can be any
        // workflow of the given entity type id as it depends on parameters from
        // the runtime.
        // Loop over all workflows of the entity type id and add them to the
        // list of the possible workflows.
        foreach ($this->getPossibleWorkflowsForEntityType($entity_type_id) as $workflow_id => $workflow) {
          /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
          $workflow = $this->workflowManager->createInstance($workflow_id);
          $workflows[$workflow_id] = $workflow;
        }
      }
      elseif ($workflow = $field_definition->getSetting('workflow')) {
        /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
        $workflow = $this->workflowManager->createInstance($workflow);
        $workflows[$workflow->getId()] = $workflow;
      }
    }
    return $workflows;
  }

  /**
   * {@inheritdoc}
   */
  public function getPossibleWorkflowsForEntityType(string $entity_type_id): array {
    $groups = array_keys($this->workflowGroupManager->getDefinitionsByEntityType($entity_type_id));
    $workflows = $this->workflowManager->getDefinitions();
    $workflows = array_filter($workflows, function (array $workflow) use ($groups): bool {
      return in_array($workflow['group'], $groups);
    });
    return $workflows;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityStateFieldDefinitions(string $entity_type_id, string $bundle_id, array $field_names = []): array {
    return array_filter($this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_id), function (FieldDefinitionInterface $field_definition) use ($field_names) {
      return empty($field_names) ?
        $field_definition->getType() == 'state' :
        $field_definition->getType() == 'state' && in_array($field_definition->getName(), $field_names);
    });
  }

}

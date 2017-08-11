<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Helper methods to deal with workflow checks.
 */
trait WorkflowTrait {

  /**
   * Asserts available transitions of an entity for a user.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with the states.
   * @param array $available_transitions
   *   The transitions to check for availability.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   The account interface object. Can be left empty.
   *
   * @throws \Exception
   *    Thrown when the expected transitions array does not exactly match the
   *    array of available options.
   */
  protected function assertAvailableTransitions(FieldableEntityInterface $entity, array $available_transitions, AccountInterface $user = NULL) {
    $allowed_transitions = $this->getAvailableTransitions($entity, $user);
    $allowed_transitions = array_values($allowed_transitions);
    sort($allowed_transitions);
    sort($available_transitions);
    if ($allowed_transitions != $available_transitions) {
      $message = "States found were different that states passed.\n";
      $message .= "Entity: " . $entity->label() . "\n";
      $message .= "User: " . $user->label() . "\n";
      $message .= "Allowed states: " . implode(', ', $allowed_transitions) . "\n";
      $message .= "Available/Expected states: " . implode(', ', $available_transitions) . "\n";
      throw new \Exception($message);
    }
  }

  /**
   * Returns the available transition states of an entity for the given user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with the states.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   The account interface object. Can be left empty.
   *
   * @return array
   *   An array of transition state labels.
   */
  protected function getAvailableStates(FieldableEntityInterface $entity, AccountInterface $user = NULL) {
    return $this->getWorkflowHelper()->getAvailableStatesLabels($entity, $user);
  }

  /**
   * Returns the available transitions of an entity for the given user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with the states.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   The account interface object. Can be left empty.
   *
   * @return array
   *   An array of transition labels.
   */
  protected function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $user) {
    return $this->getWorkflowHelper()->getAvailableTransitionsLabels($entity, $user);
  }

  /**
   * Returns the state field definition of an entity.
   *
   * In the current project every entity with a state has only one state field
   * so this method returns the first available field definitions of the
   * entity's field definitions otherwise it returns NULL.
   *
   * @param string $entity_type_id
   *   The entity type ID for which to return the state field definition.
   * @param string $bundle_id
   *   The bundle ID for which to return the state field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   Returns the state field definition of the entity or NULL if none is
   *    found.
   */
  protected function getEntityStateFieldDefinition($entity_type_id, $bundle_id) {
    return $this->getWorkflowHelper()->getEntityStateFieldDefinition($entity_type_id, $bundle_id);
  }

  /**
   * Returns the StateItem field for a given entity.
   *
   * In the current project every entity with a state has only one state field
   * so this method returns the first available field definitions of the
   * entity's field definitions.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return the state field.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The state field.
   *
   * @throws \Exception
   *   Thrown when the entity does not have a state field.
   */
  protected function getEntityStateField(FieldableEntityInterface $entity) {
    return $this->getWorkflowHelper()->getEntityStateField($entity);
  }

  /**
   * Returns the workflow helper service.
   *
   * @return \Drupal\joinup_core\WorkflowHelperInterface
   *   The workflow helper service.
   */
  protected function getWorkflowHelper() {
    return \Drupal::service('joinup_core.workflow.helper');
  }

  /**
   * Mapping of human readable names to machine names.
   *
   * @return array
   *   Field mapping.
   */
  protected static function workflowStateAliases() {
    return [
      'deletion request' => 'deletion_request',
      'needs update' => 'needs_update',
      'new' => '__new__',
    ];
  }

  /**
   * Translates human readable workflow states to machine names.
   *
   * @param string $state
   *   The human readable workflow state. Case insensitive.
   *
   * @return string
   *   The machine name of the workflow state.
   */
  protected static function translateWorkflowStateAlias($state) {
    $state = strtolower($state);
    $aliases = self::workflowStateAliases();
    if (array_key_exists($state, $aliases)) {
      $state = $aliases[$state];
    }
    return $state;
  }

}

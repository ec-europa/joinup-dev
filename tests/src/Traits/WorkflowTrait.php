<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Helper methods to deal with workflow checks.
 */
trait WorkflowTrait {

  /**
   * Asserts available states of an entity for a user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity with the states.
   * @param mixed $user
   *    The account interface object. Can be left empty.
   * @param array $available_states
   *    The states to check for availability.
   *
   * @throws \Exception
   *    Thrown when the entity has no state fields.
   */
  private function assertAvailableStates(EntityInterface $entity, $user, array $available_states) {
    $field_definition = $this->getEntityStateFieldDefinition($entity);
    if ($field_definition == NULL) {
      throw new \Exception("No state fields were found in the entity.");
    }

    if ($user == NULL) {
      $user = \Drupal::currentUser();
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    \Drupal::service('joinup_core.workflow.user_provider')->setUser($user);
    $field = $entity->{$field_definition->getName()}->first();
    $allowed_transitions = $field->getTransitions();

    $allowed_states = array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getToState()->getLabel();
    }, $allowed_transitions);
    $allowed_states = array_values($allowed_states);
    sort($allowed_states);
    sort($available_states);
    if ($allowed_states != $available_states) {
      $message = "States found were different that states passed.\n";
      $message .= "User: {$user->getAccountName()}\n";
      $message .= "Solution: {$entity->label()}\n";
      $message .= "Solution's state: {$field->value}\n";
      $message .= "Allowed states: " . implode(', ', $allowed_states) . "\n";
      $message .= "Available/Expected states: " . implode(', ', $available_states) . "\n";
      throw new \Exception($message);
    }
  }

  /**
   * Returns the state field definition of an entity.
   *
   * In the current project every entity with a state has only one state field
   * so this method returns the first available field definitions of the
   * entity's field definitions otherwise it returns NULL.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *    The entity that has the state field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *    Returns the state field definition of the entity or NULL if none is
   *    found.
   */
  private function getEntityStateFieldDefinition(EntityInterface $entity) {
    /** @var FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $entity->getFieldDefinitions();
    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() == 'state') {
        return $field_definition;
      }
    }

    return NULL;
  }

}

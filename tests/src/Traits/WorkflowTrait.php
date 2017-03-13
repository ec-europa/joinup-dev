<?php

namespace Drupal\joinup\Traits;

use Drupal\Core\Entity\EntityInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Helper methods to deal with workflow checks.
 */
trait WorkflowTrait {

  /**
   * Asserts available transitions of an entity for a user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the states.
   * @param array $available_transitions
   *   The transitions to check for availability.
   * @param object|null $user
   *   The account interface object. Can be left empty.
   *
   * @throws \Exception
   *    Thrown when the expected transitions array does not exactly match the
   *    array of available options.
   */
  protected function assertAvailableTransitions(EntityInterface $entity, array $available_transitions, $user = NULL) {
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
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the states.
   * @param object|null $user
   *   The account interface object. Can be left empty.
   *
   * @return array
   *   An array of transition state labels.
   */
  protected function getAvailableStates(EntityInterface $entity, $user) {
    if ($user == NULL) {
      $user = \Drupal::currentUser();
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    \Drupal::service('joinup_core.workflow.user_provider')->setUser($user);

    $field = $this->getEntityStateField($entity);
    $allowed_transitions = $field->getTransitions();

    $allowed_states = array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getToState()->getLabel();
    }, $allowed_transitions);

    return $allowed_states;
  }

  /**
   * Returns the available transitions of an entity for the given user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity with the states.
   * @param object|null $user
   *   The account interface object. Can be left empty.
   *
   * @return array
   *   An array of transition labels.
   */
  protected function getAvailableTransitions(EntityInterface $entity, $user) {
    if ($user == NULL) {
      $user = \Drupal::currentUser();
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    \Drupal::service('joinup_core.workflow.user_provider')->setUser($user);

    $field = $this->getEntityStateField($entity);

    return array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getLabel();
    }, $field->getTransitions());
  }

  /**
   * Returns the state field definition of an entity.
   *
   * In the current project every entity with a state has only one state field
   * so this method returns the first available field definitions of the
   * entity's field definitions otherwise it returns NULL.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that has the state field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   Returns the state field definition of the entity or NULL if none is
   *    found.
   */
  protected function getEntityStateFieldDefinition(EntityInterface $entity) {
    /** @var FieldDefinitionInterface[] $field_definitions */
    $field_definitions = $entity->getFieldDefinitions();
    foreach ($field_definitions as $field_definition) {
      if ($field_definition->getType() == 'state') {
        return $field_definition;
      }
    }

    return NULL;
  }

  /**
   * Returns the StateItem field for a given entity.
   *
   * In the current project every entity with a state has only one state field
   * so this method returns the first available field definitions of the
   * entity's field definitions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to return the state field.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The state field.
   *
   * @throws \Exception
   *   Thrown when the entity does not have a state field.
   */
  protected function getEntityStateField(EntityInterface $entity) {
    $field_definition = $this->getEntityStateFieldDefinition($entity);
    if ($field_definition == NULL) {
      throw new \Exception('No state fields were found in the entity.');
    }
    return $entity->{$field_definition->getName()}->first();
  }

}

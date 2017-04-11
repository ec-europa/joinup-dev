<?php

namespace Drupal\joinup_core;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Contains helper methods to retrieve workflow related data from entities.
 */
class WorkflowHelper implements WorkflowHelperInterface {

  /**
   * The current user proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The service that provides users to workflow guard classes.
   *
   * @var \Drupal\joinup_core\WorkflowUserProvider
   */
  protected $userProvider;

  /**
   * Constructs a WorkflowHelper.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The service that contains the current user.
   * @param \Drupal\joinup_core\WorkflowUserProvider $userProvider
   *   The service that provides users to workflow guard classes.
   */
  public function __construct(AccountProxyInterface $currentUser, WorkflowUserProvider $userProvider) {
    $this->currentUser = $currentUser;
    $this->userProvider = $userProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableStates(FieldableEntityInterface $entity, AccountInterface $user = NULL) {
    if ($user == NULL) {
      $user = $this->currentUser;
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    $this->userProvider->setUser($user);

    $field = $this->getEntityStateField($entity);
    $allowed_transitions = $field->getTransitions();

    $allowed_states = array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getToState()->getLabel();
    }, $allowed_transitions);

    return $allowed_states;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $user) {
    if ($user == NULL) {
      $user = $this->currentUser;
    }

    // Set the user to the workflow user provider so that states available are
    // retrieved for the specific account.
    $this->userProvider->setUser($user);

    $field = $this->getEntityStateField($entity);

    return array_map(function (WorkflowTransition $transition) {
      return (string) $transition->getLabel();
    }, $field->getTransitions());
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntityStateFieldDefinition(FieldableEntityInterface $entity) {
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
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return the state field.
   *
   * @return \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   *   The state field.
   *
   * @throws \Exception
   *   Thrown when the entity does not have a state field.
   */
  public function getEntityStateField(FieldableEntityInterface $entity) {
    $field_definition = $this->getEntityStateFieldDefinition($entity);
    if ($field_definition == NULL) {
      throw new \Exception('No state fields were found in the entity.');
    }
    return $entity->{$field_definition->getName()}->first();
  }

}

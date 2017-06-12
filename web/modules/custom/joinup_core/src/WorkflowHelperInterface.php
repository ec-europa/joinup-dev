<?php

namespace Drupal\joinup_core;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;

/**
 * Interface for services that provide workflow related helper methods.
 */
interface WorkflowHelperInterface {

  /**
   * Returns the available transition states labels of an entity for given user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with the states.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account interface object. Can be left empty.
   *
   * @return array
   *   An array of transition state labels.
   */
  public function getAvailableStatesLabels(FieldableEntityInterface $entity, AccountInterface $account = NULL);

  /**
   * Returns the available transitions labels of an entity for the given user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with the states.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account interface object. Can be left empty.
   *
   * @return array
   *   An array of transition labels.
   */
  public function getAvailableTransitionsLabels(FieldableEntityInterface $entity, AccountInterface $account = NULL);

  /**
   * Returns the available transition states of an entity for the given user.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity with the states.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account interface object. Can be left empty.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\WorkflowTransition[]
   *   An array of transition objects.
   */
  public function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $account = NULL);

  /**
   * Returns the state field definitions of an entity.
   *
   * @param string $entity_type_id
   *   The entity type ID for which to return the state field definitions.
   * @param string $bundle_id
   *   The bundle ID for which to return the state field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   Returns an array of state field definitions.
   */
  public function getEntityStateFieldDefinitions($entity_type_id, $bundle_id);

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
   *   found.
   */
  public function getEntityStateFieldDefinition($entity_type_id, $bundle_id);

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
  public function getEntityStateField(FieldableEntityInterface $entity);

  /**
   * Returns whether the entity has a state field and supports workflow.
   *
   * @param string $entity_type_id
   *   The entity type ID for which to check if a state field exists.
   * @param string $bundle_id
   *   The bundle ID for which to check if a state field exists.
   *
   * @return bool
   *   TRUE if the entity has a state field. FALSE otherwise.
   */
  public function hasEntityStateField($entity_type_id, $bundle_id);

  /**
   * Checks if a state is set as published in a certain workflow.
   *
   * @param string $state_id
   *   The ID of the state to check.
   * @param \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow
   *   The workflow the state belongs to.
   *
   * @return bool
   *   TRUE if the state is set as published in the workflow, FALSE otherwise.
   *
   * @throwns \InvalidArgumentException
   *   Thrown when the workflow is not plugin based, because this is required to
   *   retrieve the publication state from the workflow states.
   */
  public function isWorkflowStatePublished($state_id, WorkflowInterface $workflow);

}

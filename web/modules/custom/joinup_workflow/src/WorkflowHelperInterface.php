<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Interface for services that provide workflow related helper methods.
 */
interface WorkflowHelperInterface {

  /**
   * Returns the available target states of an entity for the given user.
   *
   * If no user is passed, the logged in user is checked. If no user is logged
   * in, an anonymous account is passed.
   *
   * This will return all target states that are available to the user, meaning
   * the transition states and the current state if it is allowed to update the
   * entity without changing the state.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account for which to check the available workflow states. If
   *   omitted the currently logged in user will be checked.
   *
   * @return string[]
   *   An array of available target workflow states.
   */
  public function getAvailableTargetStates(FieldableEntityInterface $entity, AccountInterface $account = NULL): array;

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
  public function getAvailableTransitionsLabels(FieldableEntityInterface $entity, AccountInterface $account = NULL): array;

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
  public function getAvailableTransitions(FieldableEntityInterface $entity, AccountInterface $account = NULL): array;

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
  public function getEntityStateFieldDefinitions(string $entity_type_id, string $bundle_id): array;

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
  public function getEntityStateFieldDefinition(string $entity_type_id, string $bundle_id): ?FieldDefinitionInterface;

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
  public function getEntityStateField(FieldableEntityInterface $entity): StateItemInterface;

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
  public function hasEntityStateField(string $entity_type_id, string $bundle_id): bool;

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
  public function isWorkflowStatePublished(string $state_id, WorkflowInterface $workflow): bool;

  /**
   * Returns the workflow related to an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $state_field_name
   *   The state field name. If not passed, it will be searched.
   *
   * @return \Drupal\workflows\WorkflowInterface|null
   *   The workflow object or null if it was not found.
   */
  public function getWorkflow(EntityInterface $entity, string $state_field_name = NULL): ?WorkflowInterface;

  /**
   * Finds the transition given an entity that is being updated.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $state_field_name
   *   The state field name. If not passed, it will be searched.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\WorkflowTransition|null
   *   The transition object or null if it was not found.
   */
  public function findTransitionOnUpdate(EntityInterface $entity, string $state_field_name = NULL): ?WorkflowTransition;

  /**
   * Checks whether the user has at least one of the provided roles.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param array $roles
   *   A list of role ids indexed by keys 'own' and 'any' which represents
   *   ownership and a second level of 'roles' for system roles and
   *   'og_roles' for og roles.
   *
   * @return bool
   *   True if the user has at least one of the roles provided.
   */
  public function userHasOwnAnyRoles(EntityInterface $entity, AccountInterface $account, array $roles): bool;

  /**
   * Checks if the user has at least one required role in the entity's group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param array $roles
   *   A list of role ids indexed by 'roles' for system roles and
   *   'og_roles' for og roles.
   *
   * @return bool
   *   True if the user has at least one of the roles provided.
   */
  public function userHasRoles(EntityInterface $entity, AccountInterface $account, array $roles): bool;

}

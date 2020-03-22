<?php

declare(strict_types = 1);

namespace Drupal\state_machine_permissions;

/**
 * Interface StateMachinePermissionsHelperInterface.
 */
interface StateMachinePermissionsHelperInterface {

  /**
   * Returns a list of state field definitions.
   *
   * @return array
   *   An associative array where level 0 is the entity type id, level 1 are
   *   the bundles with a state field and each bundle is an array of state
   *   fields that are attached to the entity.
   */
  public function getStateFieldMap(): array;

  /**
   * Returns a list of possible workflows for a given bundle.
   *
   * By default, we cannot always assume the one workflow the bundle can get
   * because in some cases, the state field can use a callback to retrieve the
   * workflow to use.
   * In these case, the method will return all workflows related to the given
   * entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id that the bundle belongs to.
   * @param string $bundle
   *   The entity bundle.
   * @param array $field_names
   *   (optional) A list of state fields to derive the workflows from. If left
   *   empty, all fields will be examined.
   *
   * @return \Drupal\state_machine\Plugin\Workflow\Workflow[]
   *   An array of workflows that can match the given bundle. If one workflow
   *   is found, it is still returned as an array.
   */
  public function getPossibleWorkflowsForBundle(string $entity_type_id, string $bundle, array $field_names = []): array;

  /**
   * Returns a list of possible workflows for the entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return array
   *   The array of possible workflow definition arrays.
   */
  public function getPossibleWorkflowsForEntityType(string $entity_type_id): array;

  /**
   * Returns a list of state field definitions.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle_id
   *   The entity bundle.
   * @param array $field_names
   *   (optional) An array of field names to return. Returns all state field
   *   definitions of the bundle if left empty.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions.
   */
  public function getEntityStateFieldDefinitions(string $entity_type_id, string $bundle_id, array $field_names = []): array;

}

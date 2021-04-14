<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Entity\Query\Sparql;

use Drupal\Core\Entity\Query\ConditionInterface;

/**
 * Defines the entity query condition interface for SPARQL.
 */
interface SparqlConditionInterface extends ConditionInterface {

  /**
   * The subject variable name.
   *
   * @var string
   */
  public const ID_KEY = '?entity';

  /**
   * Adds a mapping requirement to the condition list.
   *
   * The field mapping requirement can be used either to add a field with
   * multiple mappings or external requirements like a mapping for sort or group
   * arguments.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   * @param string|null $column
   *   (optional) The field column. If empty, the main property will be used.
   *
   * @throws \Drupal\sparql_entity_storage\Exception\UnmappedFieldException
   *   If the field is unmapped.
   */
  public function addFieldMappingRequirement(string $entity_type_id, string $field, ?string $column = NULL): void;

}

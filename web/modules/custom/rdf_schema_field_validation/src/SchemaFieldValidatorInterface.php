<?php

declare(strict_types = 1);

namespace Drupal\rdf_schema_field_validation;

/**
 * Interface for services that validate that fields are defined in a schema.
 */
interface SchemaFieldValidatorInterface {

  /**
   * Checks if a field is defined in the ontology of it's class.
   *
   * @param string $entity_type_id
   *   The entity type id of the class defined in the ontology.
   * @param string $bundle
   *   The bundle of the class defined in the ontology.
   * @param string $field_name
   *   The field name.
   * @param string $column
   *   The column name.
   *
   * @return bool
   *   Whether the field name is defined in the schema.
   */
  public function isDefinedInSchema(string $entity_type_id, string $bundle, string $field_name, string $column = ''): bool;

  /**
   * Checks whether an entity bundle has a validation schema definition.
   *
   * This is different with the field mapping itself as an entity can have a
   * mapping but it does not necessarily have to be part of an ontology.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   *
   * @return bool
   *   Whether the bundle has a schema associated with it and can validate
   *   field schema definitions.
   */
  public function hasSchemaDefinition(string $entity_type_id, string $bundle): bool;

}

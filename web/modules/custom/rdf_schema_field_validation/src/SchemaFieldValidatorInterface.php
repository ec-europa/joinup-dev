<?php

declare(strict_types = 1);

namespace Drupal\rdf_schema_field_validation;

/**
 * Interface SchemaFieldValidatorInterface.
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
  public function isDefinedInSchema(string $entity_type_id, string $bundle, string $field_name, string $column = '');

}

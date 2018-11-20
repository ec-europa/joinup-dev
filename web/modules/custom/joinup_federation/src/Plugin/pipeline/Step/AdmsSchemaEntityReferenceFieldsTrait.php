<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

/**
 * Allows collecting entity reference fields included in ADMS-AP schema.
 */
trait AdmsSchemaEntityReferenceFieldsTrait {

  /**
   * Static cache.
   *
   * @var array
   */
  protected $admsSchemaEntityReferenceFields = [];

  /**
   * Returns all entity reference fields belonging to ADMS-AP schema.
   *
   * @param string $bundle
   *   The bundle.
   * @param string[]|null $target_entity_type_ids
   *   (optional) A list of target entity types to filter on. If missed, it will
   *   not apply any filter based on the target entity type ID.
   *
   * @return string[]
   *   Associative array with the list of fields keyed by field name and having
   *   the target entity type ID as value.
   */
  protected function getAdmsSchemaEntityReferenceFields(string $bundle, array $target_entity_type_ids = NULL): array {
    if (!isset($this->admsSchemaEntityReferenceFields[$bundle])) {
      $this->admsSchemaEntityReferenceFields[$bundle] = [];
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
      foreach ($this->entityFieldManager->getFieldDefinitions('rdf_entity', $bundle) as $field_name => $field_definition) {
        // Check for entity reference fields with stored values.
        if (($field_definition->getType() === 'entity_reference') && !$field_definition->isComputed()) {
          // Limit to target entity type IDs, if it has been requested.
          $target_entity_type_id = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
          if (($target_entity_type_ids === NULL) || in_array($target_entity_type_id, $target_entity_type_ids)) {
            // Deal only with ADMS-AP schema fields.
            if ($this->rdfSchemaFieldValidator->isDefinedInSchema('rdf_entity', $bundle, $field_name)) {
              $this->admsSchemaEntityReferenceFields[$bundle][$field_name] = $target_entity_type_id;
            }
          }
        }
      }
    }
    return $this->admsSchemaEntityReferenceFields[$bundle];
  }

}

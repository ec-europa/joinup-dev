<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\pipeline\Step;

/**
 * Allows collecting entity reference fields, included in ADMS-AP schema.
 */
trait AdmsSchemaEntityReferenceFieldsTrait {

  /**
   * Static cache.
   *
   * @var array
   */
  protected $admsSchemaEntityReferenceFields = [];

  /**
   * Returns all entity reference field belonging to ADMS-AP schema.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return string[]
   *   A list of field names.
   */
  protected function getAdmsSchemaEntityReferenceFields(string $bundle): array {
    if (!isset($this->admsSchemaEntityReferenceFields[$bundle])) {
      $this->admsSchemaEntityReferenceFields[$bundle] = [];
      foreach ($this->entityFieldManager->getFieldDefinitions('rdf_entity', $bundle) as $field_name => $field_definition) {
        if (
          $field_definition->getType() === 'entity_reference'
          && $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'rdf_entity'
          && !$field_definition->isComputed()
          && $this->rdfSchemaFieldValidator->isDefinedInSchema('rdf_entity', $bundle, $field_name)
        ) {
          $this->admsSchemaEntityReferenceFields[$bundle][] = $field_name;
        }
      }
    }
    return $this->admsSchemaEntityReferenceFields[$bundle];
  }

}

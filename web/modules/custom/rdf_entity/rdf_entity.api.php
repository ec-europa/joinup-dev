<?php

/**
 * @file
 * Hooks and documentation related to rdf_entities.
 */

/**
 * Alters the field configuration for fields related to rdf entities.
 *
 * @param \Drupal\field\Entity\FieldStorageConfig $storage
 *   The field configuration storage entity.
 * @param array &$values
 *   An associative array of field values. This array include any additional
 *   data a field formatter includes.
 *
 * @ingroup rdf_entity_api
 */
function hook_rdf_apply_default_fields_alter(\Drupal\field\Entity\FieldStorageConfig $storage, &$values) {
  if ($storage->getType() == 'text_long') {
    // Handle multiple values in a field.
    foreach ($values as &$value) {
      $value['format'] == 'my_custom_persistent_filter';
    }
  }
}

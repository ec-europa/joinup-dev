<?php

/**
 * @file
 * Hooks and documentation related to SPARQL entity storage.
 */

/**
 * Alters the field configuration for fields of entities with SPARQL storage.
 *
 * @param \Drupal\field\Entity\FieldStorageConfig $storage
 *   The field configuration storage entity.
 * @param array &$values
 *   An associative array of field values. This array include any additional
 *   data a field formatter includes.
 */
function hook_sparql_apply_default_fields_alter(\Drupal\field\Entity\FieldStorageConfig $storage, array &$values) {
  if ($storage->getType() == 'text_long') {
    // Handle multiple values in a field.
    foreach ($values as &$value) {
      $value['format'] == 'my_custom_persistent_filter';
    }
  }
}

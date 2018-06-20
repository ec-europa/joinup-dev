<?php

namespace Drupal\joinup_core;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a custom field storage definition class.
 *
 * In order to use hook_entity_bundle_field_info(), a field definition that
 * marks the field as not base one must be used.
 *
 * @see \Drupal\entity_test\FieldStorageDefinition
 * @see https://www.drupal.org/node/2280639
 */
class FieldStorageDefinition extends BaseFieldDefinition {

  /**
   * {@inheritdoc}
   */
  public function isBaseField() {
    return FALSE;
  }

}

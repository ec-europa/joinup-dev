<?php

/**
 * @file
 * Post update functions for the Joinup Events module.
 */

declare(strict_types = 1);

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Remove the unused 'Additional address info' field.
 */
function joinup_event_post_update_remove_additional_address_info(): void {
  // By deleting the field storage, the field instance is also automatically
  // deleted.
  FieldStorageConfig::loadByName('node', 'field_event_adtl_address_info')->delete();
}

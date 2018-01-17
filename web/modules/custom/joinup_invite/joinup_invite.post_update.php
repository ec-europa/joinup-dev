<?php

/**
 * @file
 * Post update functions for the Joinup Invite module.
 */

/**
 * Install the new entity types that are provided with this module.
 */
function joinup_invite_post_update_install_entity_types() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  foreach ($entity_type_manager->getDefinitions() as $entity_type) {
    if ($entity_type->getProvider() === 'joinup_invite') {
      $update_manager->installEntityType($entity_type);
    }
  }
}

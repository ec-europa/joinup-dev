<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\joinup_featured\FeaturedContentInterface;

/**
 * Migrate site wide featured content to meta entities.
 */
function joinup_core_post_update_0106600(): string {
  $count = [];
  $entity_type_manager = \Drupal::entityTypeManager();

  foreach (['node', 'rdf_entity'] as $entity_type_id) {
    $count[$entity_type_id] = 0;
    $storage = $entity_type_manager->getStorage($entity_type_id);
    $entity_ids = $storage
      ->getQuery()
      ->condition('field_site_featured', TRUE)
      ->execute();

    foreach ($storage->loadMultiple($entity_ids) as $entity) {
      if ($entity instanceof FeaturedContentInterface) {
        $entity->feature();
        $count[$entity_type_id]++;
      }
    }
  }

  return 'Featured entities: ' . http_build_query($count, '', ', ');
}

<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\sparql_entity_storage\UriEncoder;

/**
 * Re-run the update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106600(?array &$sandbox = NULL): string {
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = [
    'rdf_entity' => $entity_type_manager->getStorage('rdf_entity'),
    'node' => $entity_type_manager->getStorage('node'),
  ];
  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');

  if (empty($sandbox['entity_ids'])) {
    $rdf_bundles = [
      'collection',
      'solution',
      'asset_release',
      'asset_distribution',
    ];
    $results = \Drupal::database()
      ->query("SELECT path, alias FROM {path_alias} p WHERE p.alias LIKE '/solution/%'")
      ->fetchAll();

    $entity_ids = [
      'rdf_entity' => array_fill_keys($rdf_bundles, []),
      'node' => [],
    ];

    foreach ($results as $result) {
      [$entity_type_id, $entity_id] = explode('/', ltrim($result->path, '/'), 2);
      if ($entity_type_id === 'rdf_entity') {
        // RDF entity IDs are URI encoded.
        $entity_id = UriEncoder::decodeUrl($entity_id);
      }
      elseif ($entity_type_id !== 'node') {
        // Not RDF entity, not node.
        continue;
      }

      // Only store the ID if the entity exists.
      if ($entity = $storage[$entity_type_id]->load($entity_id)) {
        $entity_ids[$entity_type_id][$entity->bundle()][] = $entity_id;
      }
    }

    $sandbox['entity_ids'] = [];
    foreach ($entity_ids as $entity_type_id => $bundles) {
      foreach ($bundles as $entity_ids_per_bundle) {
        foreach ($entity_ids_per_bundle as $id) {
          $sandbox['entity_ids'][$id] = $entity_type_id;
        }
      }
    }
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_ids = array_splice($sandbox['entity_ids'], 0, 100);
  $ids_per_entity_type = [];
  // Re-arrange back per entity-type.
  foreach ($entity_ids as $id => $entity_type_id) {
    $ids_per_entity_type[$entity_type_id][] = $id;
  }

  foreach ($ids_per_entity_type as $entity_type_id => $ids) {
    foreach ($storage[$entity_type_id]->loadMultiple($ids) as $entity) {
      if ($entity->bundle() === 'asset_release' && $entity->field_isr_release_number->isEmpty()) {
        // There are a number of releases that do not have a release number,
        // even though it is a mandatory field. Aliases fail to be created for
        // these entities. These will be handled in ISAICP-6217.
        // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6217
        continue;
      }
      $alias_generator->updateEntityAlias($entity, 'bulkupdate');
    }
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) empty($sandbox['entity_ids']);
  return "Processed {$sandbox['count']}/{$sandbox['max']}";
}

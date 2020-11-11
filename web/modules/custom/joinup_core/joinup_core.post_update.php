<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

/**
 * Re-run the update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106600(?array &$sandbox = NULL): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  if (empty($sandbox['entity_ids'])) {
    // We process first the collections, solutions and releases because they're
    // parents for the rest of entities and their alias is used in children
    // alias computing.
    $sandbox['entity_ids']['rdf_entity'] = $rdf_storage->getQuery()->condition('rid', 'collection')->execute();
    $sandbox['entity_ids']['rdf_entity'] += $rdf_storage->getQuery()->condition('rid', 'solution')->execute();
    $sandbox['entity_ids']['rdf_entity'] += $rdf_storage->getQuery()->condition('rid', 'asset_release')->execute();
    $sandbox['entity_ids']['rdf_entity'] += $rdf_storage->getQuery()->condition('rid', [
      'collection',
      'solution',
      'asset_release',
    ], 'NOT IN')->execute();
    $sandbox['entity_ids']['node'] = $node_storage->getQuery()->execute();
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']['rdf_entity']) + count($sandbox['entity_ids']['node']);
  }

  if (empty($sandbox['entity_ids']['rdf_entity'])) {
    $storage = $node_storage;
    $entity_ids = array_splice($sandbox['entity_ids']['node'], 0, 100);
  }
  else {
    $storage = $rdf_storage;
    $entity_ids = array_splice($sandbox['entity_ids']['rdf_entity'], 0, 100);
  }

  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');
  foreach ($storage->loadMultiple($entity_ids) as $entity) {
    if ($entity->bundle() === 'asset_release' && $entity->field_isr_release_number->isEmpty()) {
      // There are a number of releases that do not have a release number, even
      // though it is a mandatory field. Aliases fail to be created for these
      // entities. These will be handled in ISAICP-6217.
      // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6217
      continue;
    }
    $alias_generator->updateEntityAlias($entity, 'bulkupdate');
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) (empty($sandbox['entity_ids']['rdf_entity']) && empty($sandbox['entity_ids']['node']));
  return "Processed {$sandbox['count']}/{$sandbox['max']}";
}

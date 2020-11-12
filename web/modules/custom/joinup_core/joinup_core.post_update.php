<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Url;
use Drupal\sparql_entity_storage\UriEncoder;

/**
 * Re-run the update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106601(?array &$sandbox = NULL): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  if (empty($sandbox['entity_ids'])) {
    $results = \Drupal::database()->query("SELECT `path`, `alias` FROM {path_alias} p WHERE `p`.`alias` LIKE '/solution/%';")->fetchAll();
    $rdf_ids = $node_ids = [];
    foreach ($results as $result) {
      $url = Url::fromUri('internal:' . $result->path);
      if (!$url->isRouted()) {
        continue;
      }
      if (isset($url->getRouteParameters()['rdf_entity'])) {
        $entity_id = UriEncoder::decodeUrl($url->getRouteParameters()['rdf_entity']);
        $entity = $rdf_storage->load($entity_id);
        if (in_array($entity->bundle(), [
          'collection',
          'solution',
          'asset_release',
        ])) {
          $rdf_ids[$entity->bundle()][] = $entity_id;
        }
        else {
          $rdf_ids['ids'][] = $entity_id;
        }
      }
      else {
        $entity_id = $url->getRouteParameters()['node'];
        $node_ids[] = $entity_id;
      }
    }
    $sandbox['entity_ids']['rdf_entity'] = ($rdf_ids['collection'] ?? []) + ($rdf_ids['solution'] ?? []) + ($rdf_ids['asset_release'] ?? []) + $rdf_ids['ids'];
    $sandbox['entity_ids']['node'] = $node_ids ?? [];
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

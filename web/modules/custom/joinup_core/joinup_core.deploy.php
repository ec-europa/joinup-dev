<?php

/**
 * @file
 * Deploy functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API and
 * need to run _after_ the configuration is imported.
 *
 * This is applicable in most cases. However in case the update code enables
 * some functionality that is required for configuration to be successfully
 * imported, it should instead be placed in joinup_core.post_update.php.
 */

declare(strict_types = 1);

use Drupal\asset_release\Entity\AssetRelease;
use Drupal\solution\Entity\SolutionInterface;

/**
 * Delete orphaned distributions.
 */
function joinup_core_deploy_0107500(array &$sandbox = []): string {
  $sparql = \Drupal::getContainer()->get('sparql.endpoint');
  $sparql_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');

  if (empty($sandbox['ids'])) {
    $entities_query = <<<QUERY
SELECT DISTINCT ?id
WHERE {
  ?id a <http://www.w3.org/ns/dcat#Distribution> .
  ?id <http://joinup.eu/rdf_entity/group> ?group .
  FILTER NOT EXISTS { ?group a ?type }
}
QUERY;
    $entity_ids = $sparql->query($entities_query);
    $sandbox['ids'] = array_map(function (stdClass $resource): string {
      $resource = $resource->id;
      return $resource->getUri();
    }, $entity_ids->getArrayCopy());
    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['count'] = 0;
  }

  $entity_ids = array_splice($sandbox['ids'], 0, 5);
  foreach ($sparql_storage->loadMultiple($entity_ids) as $entity) {
    $entity->skip_notification = TRUE;
    $entity->delete();
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = $sandbox['count'] / $sandbox['max'];
  return "Deleted {$sandbox['count']} out of {$sandbox['max']} orphaned distributions.";
}

/**
 * Delete spam content from the specific user.
 */
function joinup_core_deploy_0107501(array &$sandbox = []): void {
  $mysql = \Drupal::database();
  $sparql = \Drupal::getContainer()->get('sparql.endpoint');
  $sparql_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  $file_storage = \Drupal::entityTypeManager()->getStorage('file');
  $user_storage = \Drupal::entityTypeManager()->getStorage('user');
  $file_system = \Drupal::service('file_system');

  $entities_query = <<<QUERY
SELECT DISTINCT ?id
WHERE {
  ?id <http://joinup.eu/owner/uid> 747137
}
QUERY;
  $entity_ids = $sparql->query($entities_query);
  $entity_ids = array_map(function (stdClass $resource): string {
    $resource = $resource->id;
    return $resource->getUri();
  }, $entity_ids->getArrayCopy());
  foreach ($sparql_storage->loadMultiple($entity_ids) as $entity) {
    if ($entity instanceof SolutionInterface || $entity instanceof AssetRelease) {
      if ($ids = $entity->getDistributionIds()) {
        foreach ($sparql_storage->loadMultiple($ids) as $entity) {
          $entity->skip_notification = TRUE;
          $entity->delete();
        }
      }
    }
    $entity->skip_notification = TRUE;
    $entity->delete();
  }

  // Delete files.
  $results = $mysql->query('select fid, filename, uri from file_managed where uid = 747137;')->fetchAll();
  foreach ($results as $result) {
    if ($file = $file_storage->load($result->fid)) {
      $file->delete();
    }
    else {
      $file_system->delete($result->uri);
    }
  }

  $user_storage->load(747137)->delete();
}

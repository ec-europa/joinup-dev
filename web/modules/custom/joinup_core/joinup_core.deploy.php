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

/**
 * Update existing custom pages if no filters or query is set.
 */
function joinup_core_deploy_0107502(&$sandbox): string {
  if (empty($sandbox['ids'])) {
    $sandbox['ids'] = \Drupal::entityQuery('node')->condition('type', 'custom_page')->execute();
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['ids']);
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $nids = array_splice($sandbox['ids'], 0, 50);
  foreach ($node_storage->loadMultiple($nids) as $entity) {
    // Avoid saving an entity that had no changes.
    $save = FALSE;
    if ($entity->hasField('field_paragraphs_body')) {
      $elements = $entity->get('field_paragraphs_body');
      for ($i = 0; $i < $elements->count(); $i++) {
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($elements->get($i)->target_id);
        if ($paragraph->bundle() == 'content_listing') {
          $values = $paragraph->field_content_listing->value;
          if (empty($values['query_presets']) && !array_key_exists('query_builder', $values)) {
            $elements->removeItem($i);
            // Caution: decrement the counter as removeItem()
            // also does a rekey().
            $i--;
            $save = TRUE;
          }
        }
      }
    }

    if ($save) {
      // Do not send emails for these changes.
      $entity->skip_notification = 1;
      $entity->save();
    }
    $sandbox['count']++;
  }

  $sandbox['#finished'] = $sandbox['count'] === $sandbox['max'];
  return "Updated {$sandbox['count']} out of {$sandbox['max']} custom pages.";
}

/**
 * Temporary unblock UID1.
 */
function joinup_core_deploy_0107503(array &$sandbox = []): void {
  /** @var \Drupal\user\UserInterface $account */
  $account = \Drupal::entityTypeManager()->getStorage('user')->load(1);
  $account->activate()->save();
  /** @var \Drupal\externalauth\ExternalAuthInterface $externalauth */
  $externalauth = \Drupal::service('externalauth.externalauth');
  $externalauth->linkExistingAccount('n0087n83', 'cas', $account);
}

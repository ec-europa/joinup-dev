<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\redirect\Entity\Redirect;

/**
 * Fix an entity that has two triples as changed date.
 */
function joinup_core_post_update_0106000(): void {
  $query = <<<Query
WITH <http://joinup.eu/asset_release/published>
DELETE {
  <http://data.europa.eu/w21/0d441e6f-a4a0-4b3b-ba2e-eb31bce43938> <http://purl.org/dc/terms/modified> "2020-02-18T18:27:41Z"^^xsd:dateTime
}
WHERE {
   <http://data.europa.eu/w21/0d441e6f-a4a0-4b3b-ba2e-eb31bce43938> <http://purl.org/dc/terms/modified> "2020-02-18T18:27:41Z"^^xsd:dateTime
}
Query;
  \Drupal::service('sparql.endpoint')->query($query);
}

/**
 * Fix all needed timestamps and created dates for content.
 */
function joinup_core_post_update_0106001(&$sandbox) {
  $connection = \Drupal::database();

  // Set the oldest revision timestamp to the created date for content.
  // The created time was retained throughout the migration but the revision
  // timestamp was set anew during the migration.
  $query = <<<QUERY
UPDATE {node_revision} nr
INNER JOIN (
    SELECT nid, MIN(vid) as min_vid, MIN(created) AS created_timestamp
    FROM node_field_revision
    GROUP BY nid
  ) nfr
  ON nr.nid = nfr.nid
SET nr.revision_timestamp = nfr.created_timestamp
WHERE nr.nid < 700000 AND nr.vid = min_vid;
QUERY;
  $connection->query($query)->execute();

  // Set the created timestamp all content revisions to the earliest revision
  // timestamp of that node. This will undo our workaround change the created
  // time upon initial publication.
  $query = <<<QUERY
UPDATE {node_field_revision} nfr
 INNER JOIN (
    SELECT nid, MIN(revision_timestamp) as revision_timestamp
    FROM node_revision
    GROUP BY nid
) nr ON nfr.nid = nr.nid
SET nfr.created = nr.revision_timestamp
WHERE nfr.nid = nr.nid AND nfr.nid >= 700000
QUERY;
  $connection->query($query)->execute();

  // Update also the node_field_data table.
  $query = <<<QUERY
UPDATE {node_field_data} nfd
  INNER JOIN (
    SELECT nid, vid, created
    FROM node_field_revision
) nfr ON nfd.vid = nfr.vid
SET nfd.created = nfr.created
WHERE nfd.vid = nfr.vid
QUERY;
  $connection->query($query)->execute();

  // Update all publication dates according to the minimum possible revision
  // timestamp.
  $query = <<<QUERY
UPDATE {node_field_revision} r, (
  SELECT
    subnfr.nid,
    MIN(subnfr.vid) as vid,
    MIN(subnr.revision_timestamp) as revision_timestamp
  FROM {node_field_revision} subnfr
  INNER JOIN {node_revision} subnr ON subnfr.vid = subnr.vid
  WHERE status = 1
  GROUP BY nid
  ORDER BY vid
) s
SET r.published_at = s.revision_timestamp
WHERE r.nid = s.nid AND r.vid >= s.vid;
QUERY;
  $connection->query($query)->execute();

  // Copy the publication date from the revisions table to the node table.
  $query = <<<QUERY
UPDATE {node_field_data} d, {node_field_revision} r
SET d.published_at = r.published_at
WHERE d.vid = r.vid;
QUERY;
  $connection->query($query)->execute();
}

/**
 * Update solution, release, distribution and community content aliases.
 */
function joinup_core_post_update_0106002(array &$sandbox): string {
  if (!isset($sandbox['entity_ids'])) {
    // First rebuild solution aliases.
    $sandbox['entity_ids']['rdf_entity'] = \Drupal::entityQuery('rdf_entity')->condition('rid', 'solution')->execute();
    // Then generate the asset release aliases.
    $sandbox['entity_ids']['rdf_entity'] += \Drupal::entityQuery('rdf_entity')->condition('rid', 'asset_release')->execute();
    // Finally, generate the distribution aliases.
    $sandbox['entity_ids']['rdf_entity'] += \Drupal::entityQuery('rdf_entity')->condition('rid', 'asset_distribution')->execute();
    $cc_bundles = ['custom_page', 'discussion', 'document', 'event', 'news'];
    $sandbox['entity_ids']['node'] = \Drupal::entityQuery('node')
      ->condition('type', $cc_bundles, 'IN')
      ->execute();
    $sandbox['current'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']['rdf_entity']) + count($sandbox['entity_ids']['node']);
  }

  $entity_type = empty($sandbox['entity_ids']['rdf_entity']) ? 'node' : 'rdf_entity';

  $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $pathalias_manager = \Drupal::getContainer()->get('path_alias.manager');
  $pathauto_generator = \Drupal::getContainer()->get('pathauto.generator');

  $regex_patterns = [
    'solution' => '#/collection/[^/]*/solution/[^/]*#',
    'asset_release' => '#/collection/[^/]*/solution/[^/]*/release/[^/]*#',
    'asset_distribution' => '#/collection/[^/]*/solution/[^/]*/distribution/[^/]*#',
    'custom_page' => '#/collection/[^/]*(?:/solution/[^/]*)/[^/]*#',
    'discussion' => '#/collection/[^/]*(?:/solution/[^/]*)/discussion/[^/]*#',
    'document' => '#/collection/[^/]*(?:/solution/[^/]*)/document/[^/]*#',
    'event' => '#/collection/[^/]*(?:/solution/[^/]*)/event/[^/]*#',
    'news' => '#/collection/[^/]*(?:/solution/[^/]*)/news/[^/]*#',
  ];

  $ids = array_slice($sandbox['entity_ids'][$entity_type], 0, 50);
  foreach ($entity_storage->loadMultiple(array_values($ids)) as $entity) {
    $sandbox['current']++;
    $old_alias = $pathalias_manager->getAliasByPath($entity->toUrl()->toString());
    $new_alias = $pathauto_generator->createEntityAlias($entity, 'insert');
    if ($old_alias === $new_alias['alias']) {
      continue;
    }

    if (preg_match($regex_patterns[$entity->bundle()], $new_alias['alias']) === FALSE) {
      throw new \Exception("'{$new_alias['alias']}' does not match the expected pattern.");
    }

    Redirect::create([
      'redirect_source' => $old_alias,
      'redirect_redirect' => 'internal:' . $new_alias['alias'],
      'language' => 'und',
      'status_code' => '301',
    ])->save();
  }

  $sandbox['#finished'] = $sandbox['current'] > $sandbox['max'] ? 1 : (float) $sandbox['current'] / (float) $sandbox['max'];
  return "Processed {$sandbox['current']} out of {$sandbox['max']}.";
}

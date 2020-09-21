<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

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

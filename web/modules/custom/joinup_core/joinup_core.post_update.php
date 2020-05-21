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

/**
 * Migrate the body field to the new paragraphs field for custom pages.
 */
function joinup_core_post_update_0106002(array &$sandbox): string {
  $db = \Drupal::database();
  /** @var \Drupal\Component\Uuid\UuidInterface $uuid */
  $uuid = \Drupal::service('uuid');

  if (!isset($sandbox['nids'])) {
    $sandbox['nids'] = $db->query("SELECT nid FROM {node_field_data} n WHERE n.type = 'custom_page'")->fetchCol();
    $sandbox['max'] = count($sandbox['nids']);
    // The paragraph ID.
    $sandbox['id'] = 0;
    // The paragraph revision ID.
    $sandbox['revision_id'] = 0;
  }

  $nids_to_process = array_splice($sandbox['nids'], 0, 150);
  foreach ($nids_to_process as $nid) {
    $sandbox['id']++;

    // Get all revision data for a given custom page.
    $revisions = $db->query("SELECT nfr.nid, nfr.vid, nr.revision_timestamp AS created, nrb.body_value AS body FROM {node_field_revision} nfr INNER JOIN {node_field_data} nfd ON nfr.nid = nfd.nid INNER JOIN {node_revision} nr ON nfr.vid = nr.vid LEFT JOIN {node_revision__body} nrb ON nfr.vid = nrb.revision_id WHERE nfd.type = 'custom_page' AND nfr.nid = :nid ORDER BY nfr.vid", [
      ':nid' => $nid,
    ])->fetchAll();

    // First update the revision tables. As we're sorted by the node revision
    // ID, the last iteration value is also used later as default values.
    foreach ($revisions as $revision) {
      $sandbox['revision_id']++;
      if (!empty($revision->body)) {
        $body_row = [
          'bundle' => 'simple_paragraph',
          'entity_id' => $sandbox['id'],
          'revision_id' => $sandbox['revision_id'],
          'langcode' => 'en',
          'delta' => 0,
          'field_body_value' => $revision->body,
          'field_body_format' => 'content_editor',
        ];
        $db->insert('paragraph_revision__field_body')->fields($body_row)->execute();
      }
      $data_row = [
        'id' => $sandbox['id'],
        'revision_id' => $sandbox['revision_id'],
        'langcode' => 'en',
        'status' => 1,
        'created' => $revision->created,
        'parent_id' => $nid,
        'parent_type' => 'node',
        'parent_field_name' => 'field_paragraphs_body',
        'behavior_settings' => 'a:0:{}',
        'default_langcode' => 1,
        'revision_translation_affected' => 1,
      ];
      $db->insert('paragraphs_item_revision_field_data')->fields($data_row)->execute();
      $db->insert('paragraphs_item_revision')->fields([
        'id' => $sandbox['id'],
        'revision_id' => $sandbox['revision_id'],
        'langcode' => 'en',
        'revision_default' => 1,
      ])->execute();

      // Add a record in the node revision 'field_paragraphs_body' field.
      $host_entity_data = [
        'bundle' => 'custom_page',
        'deleted' => 0,
        'entity_id' => $nid,
        'revision_id' => $revision->vid,
        'langcode' => 'en',
        'delta' => 0,
        'field_paragraphs_body_target_id' => $sandbox['id'],
        'field_paragraphs_body_target_revision_id' => $sandbox['revision_id'],
      ];
      $db->insert('node_revision__field_paragraphs_body')->fields($host_entity_data)->execute();
    }

    // Update main tables.
    if (!empty($revision->body)) {
      $db->insert('paragraph__field_body')->fields($body_row)->execute();
    }
    $data_row['type'] = 'simple_paragraph';
    $db->insert('paragraphs_item_field_data')->fields($data_row)->execute();
    $db->insert('paragraphs_item')->fields([
      'id' => $sandbox['id'],
      'revision_id' => $sandbox['revision_id'],
      'type' => 'simple_paragraph',
      'uuid' => $uuid->generate(),
      'langcode' => 'en',
    ])->execute();

    // Add a record in the node 'field_paragraphs_body' field.
    $db->insert('node__field_paragraphs_body')->fields($host_entity_data)->execute();
  }

  $sandbox['#finished'] = (int) empty($sandbox['nids']);

  if ($sandbox['#finished']) {
    // Cleanup stale body content.
    $db->delete('node__body')->condition('bundle', 'custom_page')->execute();
    $db->delete('node_revision__body')->condition('bundle', 'custom_page')->execute();
  }

  return "Processed {$sandbox['id']} items out of {$sandbox['max']}.";
}

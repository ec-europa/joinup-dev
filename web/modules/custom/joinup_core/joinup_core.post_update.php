<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\redirect\Entity\Redirect;

/**
 * Migrate the body field to the new paragraphs field for custom pages.
 */
function joinup_core_post_update_0106100(array &$sandbox): string {
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

/**
 * Update solution, release, distribution and community content aliases.
 */
function joinup_core_post_update_0106101(array &$sandbox): string {
  if (!isset($sandbox['entity_ids'])) {
    // First rebuild solution aliases.
    $sandbox['entity_ids']['rdf_entity'] = \Drupal::entityQuery('rdf_entity')->condition('rid', 'solution')->execute();
    // Then generate the asset release aliases.
    $sandbox['entity_ids']['rdf_entity'] += \Drupal::entityQuery('rdf_entity')->condition('rid', 'asset_release')->execute();
    // Finally, generate the distribution aliases.
    $sandbox['entity_ids']['rdf_entity'] += \Drupal::entityQuery('rdf_entity')->condition('rid', 'asset_distribution')->execute();
    $gc_bundles = ['custom_page', 'discussion', 'document', 'event', 'news'];
    $sandbox['entity_ids']['node'] = \Drupal::entityQuery('node')
      ->condition('type', $gc_bundles, 'IN')
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

/**
 * Fix the value predicates of the policy domain values.
 */
function joinup_core_post_update_0106102(): string {
  $query = <<<QUERY
DELETE { GRAPH ?g { ?s <http://joinup.eu/voc/policy-domain> ?o } }
INSERT { GRAPH ?g { ?s <http://policy_domain> ?o } }
WHERE { GRAPH ?g { ?s <http://joinup.eu/voc/policy-domain> ?o } }
QUERY;

  $results = \Drupal::getContainer()->get('sparql.endpoint')->query($query);
  $current_result = $results->current();
  $current_result = reset($current_result);
  return $current_result->getValue();
}

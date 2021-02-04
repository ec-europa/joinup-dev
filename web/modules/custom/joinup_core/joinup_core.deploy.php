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

use Drupal\paragraphs\Entity\Paragraph;

/**
 * Set community content missing policy domain.
 */
function joinup_core_deploy_0106800(array &$sandbox): string {
  $db = \Drupal::database();
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['nodes'])) {
    // Build an associative array having the group IDs as keys and their policy
    // domain IDs as values.
    $sparql = <<<SPARQL
      SELECT ?entityId ?policyDomain
        FROM NAMED <http://joinup.eu/collection/draft>
        FROM NAMED <http://joinup.eu/collection/published>
        FROM NAMED <http://joinup.eu/solution/draft>
        FROM NAMED <http://joinup.eu/solution/published>
        WHERE {
          GRAPH ?graph {
            ?entityId <http://policy_domain> ?policyDomain .
          }
        }
        # We order by graph to ensure that a published collection/solution
        # overrides a potential draft version.
        ORDER BY ASC(?graph)
SPARQL;
    $policy_domains = [];
    foreach (\Drupal::service('sparql.endpoint')->query($sparql) as $row) {
      $policy_domains[$row->entityId->getUri()] = $row->policyDomain->getUri();
    }

    // Get nodes without a policy domain as an associative array. The keys are
    // the node IDs and the values are \stdClass objects with the node type,
    // revision ID and the policy domain ID of the parent group as properties.
    $sql = <<<Query
      SELECT
        -- Add a char to node ID in order to preserve keys in array_splice(). 
        CONCAT('n', n.nid) AS nid,
        n.vid,
        n.type,
        og.og_audience_target_id AS parent_id
      FROM {node_field_data} n
      LEFT JOIN {node__field_policy_domain} pd ON n.nid = pd.entity_id
      INNER JOIN {node__og_audience} og ON n.nid = og.entity_id
      -- Only community content.
      WHERE n.type IN('discussion', 'document', 'event', 'news')
      -- Only nodes missing policy domain.
      AND pd.entity_id IS NULL
      -- Make order predictable.
      ORDER BY n.nid
Query;
    $sandbox['nodes'] = array_map(function (\stdClass $node) use ($policy_domains): \stdClass {
      // Replace the parent ID with its policy domain ID.
      $node->policy_domain = $policy_domains[$node->parent_id];
      unset($node->parent_id);
      return $node;
    }, $db->query($sql)->fetchAllAssoc('nid'));

    // Same as array_splice() but preserves numeric keys prefixed with a char.
    $sandbox['array_splice'] = function (array &$array): array {
      $values = array_splice($array, 0, 1000);
      $keys = array_map(function (string $key): int {
        return (int) substr($key, 1);
      }, array_keys($values));
      return array_combine($keys, $values);
    };
    $sandbox['count'] = count($sandbox['nodes']);
    $sandbox['progress'] = 0;
  }

  $nodes = $sandbox['array_splice']($sandbox['nodes']);
  $sandbox['progress'] += count($nodes);
  $fields = [
    'bundle',
    'entity_id',
    'revision_id',
    'langcode',
    'delta',
    'field_policy_domain_target_id',
  ];

  foreach (['node__field_policy_domain', 'node_revision__field_policy_domain'] as $table) {
    $query = $db->insert($table)->fields($fields);
    foreach ($nodes as $nid => $node) {
      $query->values(array_combine($fields, [
        $node->type,
        $nid,
        $node->vid,
        'en',
        0,
        $node->policy_domain,
      ]));
    }
    $query->execute();
  }

  $nids = array_keys($nodes);
  // Invalidate updated nodes caches.
  $storage->resetCache($nids);
  $sandbox['#finished'] = (int) empty($sandbox['nodes']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']}";
}

/**
 * Moves the data about the content listing of custom pages to paragraphs.
 */
function joinup_core_deploy_0106801(array &$sandbox): string {
  if (empty($sandbox['entity_ids'])) {
    $sandbox['entity_ids'] = \Drupal::entityQuery('node')->condition('type', 'custom_page')->execute();
    $sandbox['progress'] = 0;
    $sandbox['count'] = count($sandbox['entity_ids']);
    $sandbox['updated'] = 0;
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $entity_ids = array_splice($sandbox['entity_ids'], 0, 50);

  foreach ($node_storage->loadMultiple($entity_ids) as $custom_page) {
    if ($custom_page->get('field_cp_content_listing')->isEmpty()) {
      continue;
    }
    $cp_value = $custom_page->get('field_cp_content_listing')->value;
    // Skip if there is the field is not enabled and there are no query presets,
    // meaning that the field is not simply disabled.
    if ($cp_value['enabled'] === 0 && empty($cp_value['query_presets'])) {
      continue;
    }

    $paragraph = Paragraph::create(['type' => 'content_listing']);
    $paragraph->set('field_content_listing', $cp_value)->save();
    $paragraphs_body = $custom_page->get('field_paragraphs_body');
    $value = $paragraphs_body->getValue();
    $value[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    $paragraphs_body->setValue($value);
    $custom_page->set('field_cp_content_listing', NULL);
    $custom_page->save();
    $sandbox['updated']++;
  }

  $sandbox['progress'] += count($entity_ids);
  $sandbox['#finished'] = (int) empty($sandbox['entity_ids']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']} [{$sandbox['updated']} were updated]";
}

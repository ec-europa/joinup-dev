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

use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * Set community content missing policy domain.
 */
function joinup_core_deploy_0106800(array &$sandbox): string {
  $db = \Drupal::database();
  $storage = \Drupal::entityTypeManager()->getStorage('node');

  if (!isset($sandbox['nodes'])) {
    // Build an associative array having the group ID as key and its policy
    // domain IDs as value.
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
      $policy_domains[$row->entityId->getUri()][] = $row->policyDomain->getUri();
    }

    // Get nodes without a policy domain as an associative array. The keys are
    // the node IDs and the values are \stdClass objects with the node type,
    // revision ID and the policy domain IDs of the parent group as properties.
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
      // Replace the parent ID with its policy domain IDs.
      $node->policy_domains = $policy_domains[$node->parent_id];
      unset($node->parent_id);
      return $node;
    }, $db->query($sql)->fetchAllAssoc('nid'));

    // Same as array_splice() but preserves numeric keys prefixed with a char.
    $sandbox['array_splice'] = function (array &$array): array {
      $values = array_splice($array, 0, 300);
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
      foreach ($node->policy_domains as $delta => $policy_domain) {
        $query->values(array_combine($fields, [
          $node->type,
          $nid,
          $node->vid,
          'en',
          $delta,
          $policy_domain,
        ]));
      }
    }
    $query->execute();
  }

  $nids = array_keys($nodes);
  // Invalidate updated nodes caches.
  $storage->resetCache($nids);
  // Reindex updated nodes.
  foreach ($storage->loadMultiple($nids) as $node) {
    /** @var \Drupal\joinup_community_content\Entity\CommunityContentInterface $node */
    ContentEntity::indexEntity($node);
  }
  // Commit changes to index.
  \Drupal::getContainer()->get('search_api.post_request_indexing')->destruct();

  $sandbox['#finished'] = (int) empty($sandbox['nodes']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']}";
}

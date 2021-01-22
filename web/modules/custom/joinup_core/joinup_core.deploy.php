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

/**
 * Set community content missing policy domain.
 */
function joinup_core_deploy_0106800(array &$sandbox): string {
  $db = \Drupal::database();
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
      -- Prepend a character to 'nid' in order to preserve keys, later in
      -- array_splice(). 
      SELECT CONCAT('n', n.nid) AS nid, n.vid, n.type, og.og_audience_target_id AS parent_id FROM {node_field_data} n
      LEFT JOIN {node__field_policy_domain} pd ON n.nid = pd.entity_id
      INNER JOIN {node__og_audience} og ON n.nid = og.entity_id
      WHERE n.type IN('discussion', 'document', 'event', 'news')
      AND pd.entity_id IS NULL
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
  $sandbox['#finished'] = (int) empty($sandbox['nodes']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']}";
}

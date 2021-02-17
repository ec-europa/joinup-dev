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

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

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
  // Invalidate updated nodes caches.
  $storage->resetCache(array_keys($nodes));

  $sandbox['#finished'] = (int) empty($sandbox['nodes']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']}";
}

/**
 * Convert glossary abbreviation into term synonym (stage 2).
 */
function joinup_core_deploy_0106801(array &$sandbox): string {
  if (!isset($sandbox['terms'])) {
    $state = \Drupal::state();
    $sandbox['terms'] = $state->get('isaicp_6153');
    $sandbox['total'] = count($sandbox['terms']);
    $sandbox['progress'] = 0;
    $state->delete('isaicp_6153');
  }

  $terms_to_process = array_splice($sandbox['terms'], 0, 20);
  $terms = [];
  foreach ($terms_to_process as $term) {
    $terms[$term->nid] = $term->abbr;
  }
  /** @var \Drupal\collection\Entity\GlossaryTermInterface $glossary */
  foreach (Node::loadMultiple(array_keys($terms)) as $nid => $glossary) {
    $glossary->set('field_glossary_synonyms', $terms[$nid])->save();
  }
  $sandbox['progress'] += count($terms);

  $sandbox['#finished'] = (int) empty($sandbox['terms']);

  return "Converted {$sandbox['progress']} out of {$sandbox['total']}";
}

/**
 * Moves the data about the content listing of custom pages to paragraphs (2).
 */
function joinup_core_deploy_0106802(array &$sandbox): string {
  if (empty($sandbox['items'])) {
    $state = \Drupal::state();
    $sandbox['items'] = $state->get('isaicp_5880');
    $state->delete('isaicp_5880');
    $sandbox['progress'] = 0;
    $sandbox['count'] = count($sandbox['items']);
    $sandbox['updated'] = 0;
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $items = array_splice($sandbox['items'], 0, 20);
  // Refactor the array to be keyed by 'nid' and having 'listing' as value.
  $items = array_combine(array_column($items, 'nid'), array_column($items, 'listing'));

  foreach ($node_storage->loadMultiple(array_keys($items)) as $nid => $custom_page) {
    $paragraph = Paragraph::create(['type' => 'content_listing']);
    $cp_value = [0 => ['value' => $items[$nid]]];
    $paragraph->set('field_content_listing', $cp_value)->save();

    $paragraphs_body = $custom_page->get('field_paragraphs_body');
    $value = $paragraphs_body->getValue();
    $value[] = [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
    $paragraphs_body->setValue($value);
    $custom_page->save();
    $sandbox['updated']++;
  }

  $sandbox['progress'] += count($items);
  $sandbox['#finished'] = (int) empty($sandbox['items']);

  return "Updated {$sandbox['progress']} out of {$sandbox['count']} [{$sandbox['updated']} were updated]";
}

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

use Drupal\Core\Database\Database;
use Drupal\meta_entity\Entity\MetaEntity;
use Drupal\node\Entity\Node;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

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
 * Create 'collection_settings' meta entities for all collections.
 */
function joinup_core_deploy_0106802(array &$sandbox): string {
  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = array_values(
      \Drupal::entityTypeManager()->getStorage('rdf_entity')->getQuery()
        ->condition('rid', 'collection')
        ->execute()
    );
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['progress'] = 0;
  }

  $ids = array_splice($sandbox['ids'], 0, 50);
  foreach ($ids as $id) {
    MetaEntity::create(
      [
        'type' => 'collection_settings',
        'target' => [
          'target_type' => 'rdf_entity',
          'target_id' => $id,
        ],
        // Make this default option, even for existing content.
        'glossary_link_only_first' => TRUE,
      ]
    )->save();
  }
  $sandbox['progress'] += count($ids);
  $sandbox['#finished'] = (int) empty($sandbox['ids']);

  return "Processed {$sandbox['progress']} out of {$sandbox['total']}";
}

/**
 * Update the EIRA vocabulary.
 */
function joinup_core_deploy_0106803(array &$sandbox): void {
  // Clean up the existing graph.
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $sparql_connection->query('WITH <http://eira_skos> DELETE { ?s ?p ?o } WHERE { ?s ?p ?o } ');

  // Re import the file to update the terms.
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $filepath = __DIR__ . '/../../../../resources/fixtures/EIRA_SKOS.rdf';
  $graph = new Graph('http://eira_skos');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);

  // Repeat steps taken after importing the fixtures that target eira terms.
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
  $sparql_connection->query('WITH <http://eira_skos> INSERT INTO <http://eira_skos> { ?subject skos:topConceptOf <http://data.europa.eu/dr8> } WHERE { ?subject a skos:Concept .};');
  $sparql_connection->query('WITH <http://eira_skos> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');

  // There is one term removed and replaced. Update database records.
  $graphs = [
    'http://joinup.eu/solution/published',
    'http://joinup.eu/solution/draft',
  ];

  foreach ($graphs as $graph) {
    $query = <<<QUERY
WITH <$graph>
DELETE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/PublicPolicyImplementationApproach> }
INSERT { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/InteroperableDigitalPublicServicesImplementationOrientation> }
WHERE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/PublicPolicyImplementationApproach> }
QUERY;
    $sparql_connection->query($query);
  }
}

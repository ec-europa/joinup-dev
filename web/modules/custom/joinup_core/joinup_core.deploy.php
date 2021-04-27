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

use Solarium\Client;
use Solarium\QueryType\Update\Query\Document\Document;

/**
 * Restore the field policy domain node field into the new topic field.
 */
function joinup_core_deploy_0107100(array &$sandbox): void {
  // WARNING: Needs to run first among the deploy hooks.
  $database = \Drupal::database();
  $schema = $database->schema();
  $field_schema_definition = [
    'type' => 'varchar',
    'length' => 128,
    'not null' => TRUE,
    'description' => 'The ID of the target entity.',
  ];
  $indexes = [
    'fields' => [
      'field_topic_target_id' => $field_schema_definition,
    ],
    'indexes' => [
      'field_topic_target_id' => ['field_topic_target_id'],
    ],
  ];

  $schema->dropTable('node__field_topic');
  $schema->renameTable('node__field_topic_backup', 'node__field_topic');
  // The "changeField" happens in the deploy phase so that we can use the API
  // to perform the changes because the command to change the field name differs
  // even from mariaDB to MySQL.
  $schema->changeField('node__field_topic', 'field_policy_domain_target_id', 'field_topic_target_id', $field_schema_definition, $indexes);
  $schema->dropIndex('node__field_topic', 'field_policy_domain_target_id');
  $database->query("ALTER TABLE node__field_topic COMMENT 'Data storage for node field field_topic.'");

  $schema->dropTable('node_revision__field_topic');
  $schema->renameTable('node_revision__field_topic_backup', 'node_revision__field_topic');
  $schema->changeField('node_revision__field_topic', 'field_policy_domain_target_id', 'field_topic_target_id', $field_schema_definition, $indexes);
  $schema->dropIndex('node_revision__field_topic', 'field_policy_domain_target_id');
  $database->query("ALTER TABLE node_revision__field_topic COMMENT 'Revision archive storage for node field field_topic.'");

  $sparql_endpoint = \Drupal::getContainer()->get('sparql.endpoint');

  $query = <<<QUERY
DELETE { GRAPH <http://topic> { ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://joinup.eu/policy-domain> } }
INSERT { GRAPH <http://topic> { ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://joinup.eu/vocabulary/topic> } }
WHERE { GRAPH <http://topic> { ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://joinup.eu/policy-domain> } }
QUERY;
  $sparql_endpoint->query($query);

  // Update the references from <http://joinup.eu/policy-domain> to
  // <http://joinup.eu/vocabulary/topic> in all graphs that have the
  // topic reference.
  $query = <<<QUERY
DELETE { GRAPH ?g { ?entity <http://policy_domain> ?value } }
INSERT { GRAPH ?g { ?entity <http://joinup.eu/vocabulary/topic> ?value } }
WHERE { GRAPH ?g { ?entity <http://policy_domain> ?value } }
QUERY;
  $sparql_endpoint->query($query);

  // Change the policy-domain term in the ID of the topics.
  // The "http://joinup.eu/ontology/policy-domain" contains 39 characters. So
  // We start from character 40 but we subtract only 39 characters from the
  // length due because the characters start at 0.
  $query = <<<QUERY
WITH <http://topic>
DELETE { ?oldUri ?p ?o }
INSERT { ?newUri ?p ?o }
WHERE {
  ?oldUri ?p ?o .
  BIND(IRI(CONCAT("http://joinup.eu/ontology/topic", SUBSTR(STR(?oldUri), 40, STRLEN(STR(?oldUri)) - 39))) as ?newUri)
}
QUERY;
  $sparql_endpoint->query($query);

  // Update all references to the policy domain terms in SPARQL.
  $query = <<<QUERY
DELETE { GRAPH ?g { ?s ?p ?oldUri } }
INSERT { GRAPH ?g { ?s ?p ?newUri } }
WHERE {
  GRAPH ?g {
    ?s ?p ?oldUri .
    FILTER isIri(?oldUri) .
    FILTER (CONTAINS(STR(?oldUri), "http://joinup.eu/ontology/policy-domain")) .
    BIND(IRI(CONCAT("http://joinup.eu/ontology/topic", SUBSTR(STR(?oldUri), 40, STRLEN(STR(?oldUri)) - 39))) as ?newUri)
  }
}
QUERY;
  $sparql_endpoint->query($query);

  // Update all instances of the policy domain terms in the node field tables.
  $tables = [
    'node__field_topic',
    'node_revision__field_topic',
  ];
  foreach ($tables as $table) {
    $query = <<<QUERY
UPDATE {$table}
SET `field_topic_target_id` =
    REPLACE(`field_topic_target_id`, 'http://joinup.eu/ontology/policy-domain', 'http://joinup.eu/ontology/topic');
QUERY;
    $database->query($query);
  }

  // Update the cachetags table and rename the necessary tags.
  $query = <<<QUERY
UPDATE cachetags SET `tag` = REPLACE(tag, 'policy_domain', 'topic');
QUERY;
  $database->query($query);

  // Update the user professional domain.
  $query = <<<QUERY
UPDATE user__field_user_professional_domain
SET `field_user_professional_domain_target_id` =
  REPLACE(`field_user_professional_domain_target_id`, 'http://joinup.eu/ontology/policy-domain', 'http://joinup.eu/ontology/topic');
QUERY;
  $database->query($query);

  // Update the name of the pathauto_state.taxonomy_term in the key_value pair.
  $query = <<<QUERY
UPDATE key_value
SET `name` =
  REPLACE(`name`, 'http://joinup.eu/ontology/policy-domain', 'http://joinup.eu/ontology/topic')
WHERE collection = 'pathauto_state.taxonomy_term'
QUERY;
  $database->query($query);
}

/**
 * Update the paragraph content listings.
 */
function joinup_core_deploy_0107101(array &$sandbox): string {
  $database = \Drupal::database();
  if (empty($sandbox['items'])) {
    $sandbox['items'] = $database->query("SELECT entity_id, revision_id, delta, langcode, deleted, field_content_listing_value FROM paragraph_revision__field_content_listing WHERE field_content_listing_value LIKE '%policy_domain%' OR field_content_listing_value LIKE '%policy-domain';")->fetchAll();
    $sandbox['max'] = count($sandbox['items']);
    $sandbox['progress'] = 0;
  }

  $tables = [
    'paragraph__field_content_listing',
    'paragraph_revision__field_content_listing',
  ];

  $items = array_splice($sandbox['items'], 0, 50);
  foreach ($items as $item) {
    $value = unserialize($item->field_content_listing_value);
    $value['query_presets'] = str_replace(['policy_domain', 'policy-domain'], 'topic', $value['query_presets']);
    $item->field_content_listing_value = serialize($value);

    foreach ($tables as $table) {
      $database->update($table)
        ->fields((array) $item)
        ->condition('entity_id', $item->entity_id)
        ->condition('revision_id', $item->revision_id)
        ->condition('deleted', $item->deleted)
        ->condition('delta', $item->delta)
        ->condition('langcode', $item->langcode)
        ->execute();
    }
  }

  $sandbox['progress'] += count($items);
  $sandbox['#finished'] = ($sandbox['progress'] >= $sandbox['max']) ? 1 : (float) $sandbox['progress'] / (float) $sandbox['max'];
  return "Processed {$sandbox['progress']} out of {$sandbox['max']} items.";
}

/**
 * Reindex content with topics.
 */
function joinup_core_deploy_0107102(array &$sandbox): string {
  if (!isset($sandbox['current'])) {
    // Collections, solutions, releases, community content, with policy domain.
    $sandbox['query'] = <<<Query
(
  sm_entity_bundle:collection OR
  sm_entity_bundle:solution OR
  sm_entity_bundle:asset_release OR
  sm_entity_bundle:discussion OR
  sm_entity_bundle:document OR
  sm_entity_bundle:event OR
  sm_entity_bundle:news
) AND sm_policy_domain:['' TO *]
Query;
    $sandbox['current'] = 0;
  }

  $client = new Client();
  // Allow large documents to be indexed.
  $client->getEndpoint()->setTimeout(10);
  // Ignore unpublished content because that will be re-indexed on publication.
  $client->getEndpoint()->setCore('drupal_published');
  $select = $client->createSelect();
  // Fields to retrieve.
  $select->setFields(['id', 'sm_policy_domain']);
  // Query filters.
  $select->createFilterQuery('sm_policy_domain')->setQuery($sandbox['query']);
  // Make the order predictable.
  $select->setSorts(['id' => 'asc']);
  // Batch size.
  $select->setRows(1000);

  // Do the query.
  $response = json_decode($client->select($select)->getResponse()->getBody(), TRUE)['response'];
  $sandbox['total'] = $sandbox['total'] ?? $response['numFound'];

  if (!$response['docs']) {
    // Optimize the index for better performance.
    $optimize = $client->createUpdate()->addOptimize();
    $client->update($optimize);
    $sandbox['#finished'] = 1;
    return "Optimized 'drupal_published' index for better performance";
  }

  $update = $client->createUpdate();
  foreach ($response['docs'] as $doc) {
    // Transform URIs.
    $topics = array_map(function (string $uri): string {
      return str_replace('policy-domain', 'topic', $uri);
    }, $doc['sm_policy_domain']);
    // Atomic update: Reindex only the field, not the whole Solr document.
    $update->addDocument($update->createDocument()
      ->setKey('id', $doc['id'])
      // Add the new field.
      ->setField('sm_topic', $topics, NULL, Document::MODIFIER_SET)
      // Remove the stale field.
      ->setField('sm_policy_domain', NULL, NULL, Document::MODIFIER_SET)
    );
    $sandbox['current']++;
  }
  // Immediately commit updates.
  $update->addCommit();
  // Do the update.
  $client->update($update);

  $sandbox['#finished'] = 0;
  return "Processed {$sandbox['current']} out of {$sandbox['total']}";
}

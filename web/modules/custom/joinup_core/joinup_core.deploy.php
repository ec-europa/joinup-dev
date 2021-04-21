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

  // Update all references to the policy domain terms.
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

  $tables = [
    'node__field_topic',
    'node_revision__field_topic',
  ];
  foreach ($tables as $table) {
    $query = <<<QUERY
UPDATE {{$table}}
SET `field_topic_target_id` =
    REPLACE(field_topic_target_id, 'http://joinup.eu/ontology/policy-domain', 'http://joinup.eu/ontology/topic');
QUERY;
    $database->query($query);
  }

  // Update the cachetags table and rename the necessary tags.
  $query = <<<QUERY
UPDATE cachetags SET `tag` = REPLACE(tag, 'policy_domain', 'topic');
QUERY;
  $database->query($query);
}

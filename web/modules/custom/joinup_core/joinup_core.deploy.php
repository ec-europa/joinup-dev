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

use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\og\Entity\OgRole;
use Drupal\sparql_entity_storage\SparqlGraphStoreTrait;
use Drupal\sparql_entity_storage\UriEncoder;
use EasyRdf\Graph;

/**
 * Migrate site wide featured content to meta entities.
 */
function joinup_core_deploy_0106600(): string {
  $count = [];
  $entity_type_manager = \Drupal::entityTypeManager();

  foreach (['node', 'rdf_entity'] as $entity_type_id) {
    $count[$entity_type_id] = 0;
    $storage = $entity_type_manager->getStorage($entity_type_id);
    $entity_ids = $storage
      ->getQuery()
      ->condition('field_site_featured', TRUE)
      ->execute();

    foreach ($storage->loadMultiple($entity_ids) as $entity) {
      if ($entity instanceof FeaturedContentInterface) {
        $entity->feature();
        $count[$entity_type_id]++;
      }
    }
  }

  return 'Featured entities: ' . http_build_query($count, '', ', ');
}

/**
 * Update the EIRA SKOS file and its references.
 */
function joinup_core_deploy_0106601(): void {
  $graphs = [
    'http://joinup.eu/solution/draft',
    'http://joinup.eu/solution/published',
  ];
  $connection = \Drupal::getContainer()->get('sparql.endpoint');
  foreach ($graphs as $graph) {
    $update_query = <<<QUERY
WITH <{$graph}>
DELETE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/Ontologies> }
INSERT { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/Ontology> }
WHERE { ?entity_id <http://purl.org/dc/terms/type> <http://data.europa.eu/dr8/Ontologies> }
QUERY;
    $connection->query($update_query);
  }

  $graph_name = 'http://eira_skos';
  $connection->query("DEFINE sql:log-enable 3 CLEAR GRAPH <$graph_name>;");
  $graph_store = SparqlGraphStoreTrait::createGraphStore();
  $filepath = realpath(__DIR__ . '/../../../../resources/fixtures/EIRA_SKOS.rdf');
  $graph = new Graph($graph_name);
  $graph->parseFile($filepath);
  $graph_store->insert($graph);
}

/**
 * Re-run the update aliases for entities with the old alias.
 */
function joinup_core_deploy_0106602(?array &$sandbox = NULL): string {
  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = [
    'rdf_entity' => $entity_type_manager->getStorage('rdf_entity'),
    'node' => $entity_type_manager->getStorage('node'),
  ];
  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');

  if (empty($sandbox['entity_ids'])) {
    $rdf_bundles = [
      'collection',
      'solution',
      'asset_release',
      'asset_distribution',
    ];
    $results = \Drupal::database()
      ->query("SELECT path, alias FROM {path_alias} p WHERE p.alias LIKE '/solution/%'")
      ->fetchAll();

    $entity_ids = [
      'rdf_entity' => array_fill_keys($rdf_bundles, []),
      'node' => [],
    ];

    foreach ($results as $result) {
      [$entity_type_id, $entity_id] = explode(
        '/',
        ltrim($result->path, '/'),
        2
      );
      if ($entity_type_id === 'rdf_entity') {
        // RDF entity IDs are URI encoded.
        $entity_id = UriEncoder::decodeUrl($entity_id);
      }
      elseif ($entity_type_id !== 'node') {
        // Not RDF entity, not node.
        continue;
      }

      // Only store the ID if the entity exists.
      if ($entity = $storage[$entity_type_id]->load($entity_id)) {
        $entity_ids[$entity_type_id][$entity->bundle()][] = $entity_id;
      }
    }

    $sandbox['entity_ids'] = [];
    foreach ($entity_ids as $entity_type_id => $bundles) {
      foreach ($bundles as $entity_ids_per_bundle) {
        foreach ($entity_ids_per_bundle as $id) {
          $sandbox['entity_ids'][$id] = $entity_type_id;
        }
      }
    }
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_ids = array_splice($sandbox['entity_ids'], 0, 100);
  $ids_per_entity_type = [];
  // Re-arrange back per entity-type.
  foreach ($entity_ids as $id => $entity_type_id) {
    $ids_per_entity_type[$entity_type_id][] = $id;
  }

  foreach ($ids_per_entity_type as $entity_type_id => $ids) {
    foreach ($storage[$entity_type_id]->loadMultiple($ids) as $entity) {
      if ($entity->bundle() === 'asset_release' && $entity->field_isr_release_number->isEmpty()) {
        // There are a number of releases that do not have a release number,
        // even though it is a mandatory field. Aliases fail to be created for
        // these entities. These will be handled in ISAICP-6217.
        // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6217
        continue;
      }
      $alias_generator->updateEntityAlias($entity, 'bulkupdate');
    }
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) empty($sandbox['entity_ids']);
  return "Processed {$sandbox['count']}/{$sandbox['max']}";
}

/**
 * Ensure that all collection|solution owners also have the Facilitator role.
 */
function joinup_core_deploy_0106603(): string {
  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  $entity_type_manager = \Drupal::entityTypeManager();

  $affected = [];
  foreach (['collection', 'solution'] as $group_bundle) {
    // Retrieve all memberships which have the administrator role but are
    // lacking the facilitator role.
    $admin_role_id = "rdf_entity-$group_bundle-administrator";
    $facilitator_role_id = "rdf_entity-$group_bundle-facilitator";

    // First get all membership IDs for facilitators.
    $query = $entity_type_manager
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('roles', $facilitator_role_id, '=');
    $facilitator_ids = $query->execute();

    // Now get all membership IDs of owners that are not a facilitator.
    $query = $entity_type_manager
      ->getStorage('og_membership')
      ->getQuery()
      ->condition('roles', $admin_role_id, '=')
      ->condition('id', $facilitator_ids, 'NOT IN');
    $membership_ids = $query->execute();

    if (!empty($membership_ids)) {
      // Assign the facilitator role.
      $memberships = $entity_type_manager->getStorage('og_membership')->loadMultiple($membership_ids);
      $facilitator_role = OgRole::getRole('rdf_entity', $group_bundle, 'facilitator');
      /** @var \Drupal\og\OgMembershipInterface $membership */
      foreach ($memberships as $membership) {
        // Sanity check. Skip if the group no longer exists, we have some stale
        // memberships in the database.
        if (empty($membership->getGroup())) {
          continue;
        }
        $membership->addRole($facilitator_role)->save();
        $affected[] = $membership->getGroup()->label();
      }
    }
  }

  return 'Updated groups: ' . implode(', ', $affected);
}

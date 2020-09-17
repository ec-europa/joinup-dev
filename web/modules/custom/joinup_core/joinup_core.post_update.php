<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\redirect\Entity\Redirect;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Update aliases for entities with the old alias.
 */
function joinup_core_post_update_0106401(&$sandbox): string {
  $rdf_storage = \Drupal::entityTypeManager()->getStorage('rdf_entity');
  if (empty($sandbox['entity_ids'])) {
    $sandbox['entity_ids'] = $rdf_storage->getQuery()->execute();
    $sandbox['count'] = 0;
    $sandbox['updated'] = 0;
    $sandbox['redirects'] = 0;
    $sandbox['max'] = count($sandbox['entity_ids']);
  }

  $entity_ids = array_splice($sandbox['entity_ids'], 0, 50);

  $alias_manager = \Drupal::getContainer()->get('path_alias.manager');
  $alias_generator = \Drupal::getContainer()->get('pathauto.generator');
  $redirect_storage = \Drupal::entityTypeManager()->getStorage('redirect');
  foreach ($rdf_storage->loadMultiple($entity_ids) as $entity) {
    // Update aliases for the entity's default language and its translations.
    foreach ($entity->getTranslationLanguages() as $langcode => $language) {
      $old_alias = $alias_manager->getAliasByPath($entity->toUrl()->toString());
      /** @var \Drupal\Core\Entity\TranslatableInterface $translated_entity */
      $translated_entity = $entity->getTranslation($langcode);
      $result = $alias_generator->createEntityAlias($translated_entity, 'bulkupdate');
      if ($result) {
        $sandbox['updated']++;
        if ($old_alias === $result['alias']) {
          continue;
        }

        $redirects = $redirect_storage->loadByProperties([
          'redirect_source__path' => $old_alias,
          'redirect_redirect__uri' => 'internal:' . $result['alias'],
          'language' => $translated_entity->language(),
        ]);
        if (empty($redirects)) {
          Redirect::create([
            'redirect_source' => $old_alias,
            'redirect_redirect' => 'internal:' . $result['alias'],
            'language' => $translated_entity->language(),
            'status_code' => '301',
          ])->save();
          $sandbox['redirects']++;
        }
      }
    }
  }

  $sandbox['count'] += count($entity_ids);
  $sandbox['#finished'] = (int) empty($sandbox['entity_ids']);
  return "Processed {$sandbox['count']}/{$sandbox['max']}. {$sandbox['updated']} were updated. {$sandbox['redirects']} redirects were created.";
}

/**
 * Re-insert the new EIF vocabularies to apply new changes.
 */
function joinup_core_post_update_0106402(): void {
  $vids = [
    'eif_conceptual_model',
    'eif_interoperability_layer',
    'eif_principle',
    'eif_recommendation',
  ];

  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);
  foreach ($vids as $vocabulary) {
    $filepath = __DIR__ . "/../../../../resources/fixtures/{$vocabulary}.rdf";
    $graph_uri = "http://{$vocabulary}";
    $graph = new Graph($graph_uri);
    $sparql_connection->update("CLEAR GRAPH <{$graph_uri}>");
    $graph->parse(file_get_contents($filepath));
    $graph_store->insert($graph);
  }

  $entity_type_manager = \Drupal::entityTypeManager();
  $storage = $entity_type_manager->getStorage('taxonomy_term');
  $tids = $storage->getQuery()->condition('vid', $vids, 'IN')->execute();
  /** @var \Drupal\taxonomy\TermInterface $term */
  foreach ($storage->loadMultiple($tids) as $term) {
    ContentEntity::indexEntity($term);
  }
  /** @var \Drupal\search_api\IndexInterface $index */
  $index = $entity_type_manager->getStorage('search_api_index')->load('published');
  $index->reindex();
  $index->indexItems(-1, 'entity:taxonomy_term');
}

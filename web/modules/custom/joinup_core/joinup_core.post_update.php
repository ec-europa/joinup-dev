<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\eif\Eif;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\rdf_taxonomy\Entity\RdfTerm;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\sparql_entity_storage\UriEncoder;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Insert the new EIF vocabulary into the database.
 */
function joinup_core_post_update_0106300(): void {
  $sparql_connection = Database::getConnection('default', 'sparql_default');
  $connection_options = $sparql_connection->getConnectionOptions();
  $connect_string = "http://{$connection_options['host']}:{$connection_options['port']}/sparql-graph-crud";
  $graph_store = new GraphStore($connect_string);

  $filepath = __DIR__ . '/../../../../resources/fixtures/eif_voc.rdf';
  $graph = new Graph('http://eif_voc');
  $graph->parse(file_get_contents($filepath));
  $graph_store->insert($graph);
}

/**
 * Create the references page in the EIF Toolbox menu.
 */
function joinup_core_post_update_0106301(array &$sandbox): void {
  $menu_name = 'ogmenu-3444';
  $internal_path = Url::fromRoute('view.eif_recommendations.page', [
    'rdf_entity' => UriEncoder::encodeUrl(Eif::EIF_ID),
  ])->toUriString();
  $link = MenuLinkContent::create([
    'title' => t('Recommendations'),
    'menu_name' => $menu_name,
    'link' => ['uri' => $internal_path],
    'weight' => 4,
  ]);
  $link->save();
}

/**
 * Index the EIF terms since they are now tracked.
 */
function joinup_core_post_update_0106302(): void {
  $filepath = __DIR__ . '/../../../../resources/fixtures/eif_voc.rdf';
  $graph = new Graph('http://eif_voc');
  $graph->parse(file_get_contents($filepath));
  foreach ($graph->resources() as $resource_id => $resource) {
    if ($term = RdfTerm::load($resource_id)) {
      ContentEntity::indexEntity($term);
    }
  }

  $index = \Drupal::entityTypeManager()->getStorage('search_api_index')->load('published');
  $index->indexItems(-1, 'entity:taxonomy_term');
}

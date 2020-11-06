<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

declare(strict_types = 1);

use Drupal\Core\Database\Database;
use Drupal\Core\Url;
use Drupal\eif\Eif;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;
use Drupal\menu_link_content\Form\MenuLinkContentForm;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\sparql_entity_storage\UriEncoder;
use EasyRdf\Graph;
use EasyRdf\GraphStore;

/**
 * Fix the last updated time of node entities.
 */
function joinup_core_post_update_0106301(&$sandbox) {
  // In Joinup, all node updates through the UI always create a new revision.
  // Only updates through the API can update an entity without creating a new
  // revision. However, after moving the visit_count outside the storage, there
  // is no other functionality that can perform such a task.
  // Thus, it is safe to assume, that the "changed" property of each revision is
  // the same as the "revision_timestamp". The following query will fix all
  // cases where the entity was updated by an automatic procedure that wasn't
  // actually touching any values.
  $query = <<<QUERY
UPDATE {node_field_revision} nfr
INNER JOIN {node_revision} nr ON nfr.vid = nr.vid
SET nfr.changed = nr.revision_timestamp
WHERE nfr.changed != nr.revision_timestamp
QUERY;

  \Drupal::database()->query($query);
  $query = <<<QUERY
UPDATE {node_field_data} nfd
INNER JOIN {node_field_revision} nfr ON nfd.vid = nfr.vid
SET nfd.changed = nfr.changed
QUERY;

  \Drupal::database()->query($query);
}

/**
 * Insert the new EIF vocabulary into the database.
 */
function joinup_core_post_update_0106302(): void {
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
    $graph = new Graph("http://{$vocabulary}");
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
  $index = $entity_type_manager->getStorage('search_api_index')->load('published');
  $index->indexItems(-1, 'entity:taxonomy_term');
}

/**
 * Create the references page in the EIF Toolbox menu.
 */
function joinup_core_post_update_0106303(array &$sandbox): void {
  $menu_name = 'ogmenu-3444';
  $internal_path = Url::fromRoute('view.eif_recommendation.page', [
    'rdf_entity' => UriEncoder::encodeUrl(Eif::EIF_ID),
  ])->toUriString();
  $link = MenuLinkContentEntity::create([
    'title' => t('Recommendations'),
    'menu_name' => $menu_name,
    'link' => ['uri' => $internal_path],
    'weight' => 4,
  ]);
  $link->save();
}

/**
 * Create glossary OG menu item.
 */
function joinup_core_post_update_0106304(array &$sandbox) {
  $db = \Drupal::database();
  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
  /** @var \Drupal\Component\Uuid\UuidInterface $uuid_generator */
  $uuid_generator = \Drupal::service('uuid');

  if (!isset($sandbox['data'])) {
    $sandbox['solutions'] = [];
    /** @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $sparql_endpoint */
    $sparql_endpoint = \Drupal::service('sparql.endpoint');

    // Get all solutions, as keys, with their parent collections, as values.
    $sparql = "SELECT DISTINCT(?sid), ?cid WHERE { ?cid <http://www.w3.org/ns/dcat#dataset> ?sid . }";
    foreach ($sparql_endpoint->query($sparql)->getArrayCopy() as $record) {
      $sandbox['solutions'][$record->sid->getUri()] = $record->cid->getUri();
    }

    $sql = "SELECT
      ml.menu_name,
      -- This menu link should sink at the bottom of the menu.
      MAX(ml.weight) + 5 AS weight,
      og.og_audience_target_id AS gid
    FROM {menu_link_content_data} ml
    INNER JOIN {ogmenu_instance__og_audience} og ON SUBSTRING(ml.menu_name, 8) = og.entity_id
    GROUP BY ml.menu_name, gid, og.entity_id
    ORDER BY og.entity_id";
    $sandbox['data'] = $db->query($sql)->fetchAll();
    $sandbox['count'] = count($sandbox['data']);
    $sandbox['progress'] = 0;
    $sandbox['mid'] = (int) $db->query("SELECT MAX(id) FROM {menu_link_content}")->fetchField();
    $sandbox['created'] = \Drupal::time()->getRequestTime();
  }

  $data = array_splice($sandbox['data'], 0, 300);

  $base_table = $db->insert('menu_link_content')
    ->fields(['id', 'revision_id', 'bundle', 'uuid', 'langcode']);
  $base_revision_table = $db->insert('menu_link_content_revision')
    ->fields([
      'id', 'revision_id', 'langcode', 'revision_default', 'revision_created',
    ]);
  $data_table = $db->insert('menu_link_content_data')
    ->fields([
      'id',
      'revision_id',
      'bundle',
      'langcode',
      'title',
      'menu_name',
      'link__uri',
      'link__options',
      'external',
      'rediscover',
      'weight',
      'expanded',
      'enabled',
      'changed',
      'default_langcode',
      'revision_translation_affected',
    ]);
  $data_revision_table = $db->insert('menu_link_content_field_revision')
    ->fields([
      'id',
      'revision_id',
      'langcode',
      'title',
      'link__uri',
      'link__options',
      'external',
      'enabled',
      'changed',
      'default_langcode',
      'revision_translation_affected',
    ]);
  foreach ($data as $item) {
    $sandbox['mid']++;
    $uuid = $uuid_generator->generate();

    // Solution.
    if (isset($sandbox['solutions'][$item->gid])) {
      // Take the parent collection ID.
      $group_id = UriEncoder::encodeUrl($sandbox['solutions'][$item->gid]);
      unset($sandbox['solutions'][$item->gid]);
      // The 'EIF Toolbox' solution is a special one.
      if ($item->gid === 'http://data.europa.eu/w21/405d8980-3f06-4494-b34a-46c388a38651') {
        $enabled = 1;
      }
      else {
        $enabled = 0;
      }
      $options = ['attributes' => ['class' => ['group-menu-link-external']]];
    }
    // Collection.
    else {
      $group_id = UriEncoder::encodeUrl($item->gid);
      $enabled = 1;
      $options = [];
    }

    $values = [
      'id' => $sandbox['mid'],
      'revision_id' => $sandbox['mid'],
      'langcode' => 'en',
    ];
    $base_table->values($values + [
      'bundle' => 'menu_link_content',
      'uuid' => $uuid,
    ]);
    $base_revision_table->values($values + [
      'revision_default' => 1,
      'revision_created' => $sandbox['created'],
    ]);
    $values += [
      'title' => 'Glossary',
      'link__uri' => "route:collection.glossary_page;rdf_entity={$group_id}",
      'link__options' => serialize($options),
      'external' => 0,
      'enabled' => $enabled,
      'changed' => $sandbox['created'],
      'default_langcode' => 1,
      'revision_translation_affected' => 1,
    ];
    $data_revision_table->values($values);
    $values += [
      'bundle' => 'menu_link_content',
      'menu_name' => $item->menu_name,
      'rediscover' => 0,
      'weight' => $item->weight,
      'expanded' => 0,
    ];
    $data_table->values($values);

    $id = "menu_link_content:{$uuid}";
    $definition = [
      'menu_name' => $item->menu_name,
      'route_name' => 'collection.glossary_page',
      'route_parameters' => [
        'rdf_entity' => $group_id,
      ],
      'title' => 'Glossary',
      'class' => MenuLinkContent::class,
      'provider' => 'menu_link_content',
      'options' => $options,
      'enabled' => $enabled,
      'weight' => $item->weight,
      'metadata' => [
        'entity_id' => $sandbox['mid'],
      ],
      'form_class' => MenuLinkContentForm::class,
    ];
    $menu_link_manager->addDefinition($id, $definition);
    $sandbox['progress']++;
  }

  $transaction = $db->startTransaction();
  try {
    $base_table->execute();
    $base_revision_table->execute();
    $data_table->execute();
    $data_revision_table->execute();
  }
  catch (\Exception $exception) {
    $transaction->rollBack();
    throw new \Exception($exception->getMessage(), $exception->getCode(), $exception);
  }

  $sandbox['#finished'] = (int) empty($sandbox['data']);

  return "Processed {$sandbox['progress']} out of {$sandbox['count']}.";
}

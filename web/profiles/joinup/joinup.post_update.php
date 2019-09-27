<?php

/**
 * @file
 * Post update functions for the Joinup profile.
 */

declare(strict_types = 1);

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\entity_legal\Entity\EntityLegalDocumentVersion;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\meta_entity\Entity\MetaEntity;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;

/**
 * Enable the "Views data export" module.
 */
function joinup_post_update_install_views_data_export(): void {
  \Drupal::service('module_installer')->install(['views_data_export']);
}

/**
 * Enable modules related to geocoding.
 */
function joinup_post_update_install_geocoder(): void {
  $modules = [
    'geocoder',
    'geocoder_geofield',
    'geocoder_field',
    'geofield',
    'oe_webtools_geocoding',
    'oe_webtools_maps',
  ];
  \Drupal::service('module_installer')->install($modules);
}

/**
 * Enable the "Joinup RSS" module.
 */
function joinup_post_update_install_joinup_rss() {
  \Drupal::service('module_installer')->install(['joinup_rss']);
}

/**
 * Enable the "ISA2 Analytics" module.
 */
function joinup_post_update_install_isa2_analytics() {
  \Drupal::service('module_installer')->install(['isa2_analytics']);
}

/**
 * Enable the "config_readonly" module.
 */
function joinup_post_update_install_config_readonly() {
  \Drupal::service('module_installer')->install(['config_readonly']);
}

/**
 * Allow editing site pages.
 */
function joinup_post_update_site_pages() {
  \Drupal::service('module_installer')->install([
    'block_content',
    'block_content_permissions',
    'page_manager',
  ]);

  // As the configuration synchronization runs after the database post-updates,
  // we manually import the block content type here, in order to be able to
  // create the custom block.
  BlockContentType::create(Yaml::decode(file_get_contents(__DIR__ . '/config/install/block_content.type.simple_block.yml')))->save();
  FieldConfig::create(Yaml::decode(file_get_contents(__DIR__ . '/config/install/field.field.block_content.simple_block.body.yml')))->save();

  $body = <<<BODY
<h2>Important legal notice</h2>
<p>The information on this site is subject to a disclaimer, a copyright and rules related to personal data protection, each in line with the general <a href="http://ec.europa.eu/geninfo/legal_notices_en.htm">European Commission legal notice</a>, and terms of use.</p>
<h2>Copyright notice</h2>
<p>Unless otherwise indicated, reproduction is authorised, except for commercial purposes, provided that the source (Joinup) is acknowledged. Where prior permission must be obtained for the reproduction or use of textual and multimedia information (sound, images, software, etc.), such permission shall cancel the above-mentioned general permission and shall clearly indicate any restrictions on use.</p>
<h3>Special Rules for hosted and federated Open-Source Software projects</h3>
<p>Please note that all the Open-Source Applications (Projects), which are available through the repository on Joinup are provided by their owners (named in each case) subject to the copyright licences indicated in each case; the owners have to certify that all intellectual property rights concerning the Assets belong to them and no intellectual property rights of third parties are infringed. Please refer to the individual project for further information. Please note, that the European Commission accepts no responsibility with regard to these projects.</p>
<h3>Special Rules for interoperability solutions</h3>
<p>Reproduction is not authorized in general for the interoperability solutions. The copyright for the interoperability solutions is defined individually by the licence attached to the individual solution by its owner. Please refer to the individual solution for further information.</p>
<h2>Disclaimer</h2>
<p>The European Commission maintains this website to enhance public access to information about its initiatives and European Union policies in general. Our goal is to keep this information timely and accurate. If errors are brought to our attention, we will try to correct them. However, the Commission accepts no responsibility or liability whatsoever with regard to the information on this site.</p>
<p>This information is:</p>
<ol>
  <li>of a general nature only and is not intended to address the specific circumstances of any particular individual or entity;</li>
  <li>not necessarily comprehensive, complete, accurate or up to date; sometimes linked to external sites over which the Commission services have no control and for which the Commission assumes no responsibility;</li>
  <li>not professional or legal advice (if you need specific advice, you should always consult a suitably qualified professional).</li>
</ol>
<p>Please note that it cannot be guaranteed that a document available on-line exactly reproduces an officially adopted text. Only European Union legislation published in paper editions of the Official Journal of the European Union is deemed authentic.</p>
<p>Please also note that all interoperability solutions, which are available through the repository on Joinup are provided by their owners (named in each case) subject to the licences indicated in each case; the owners have to certify that all intellectual property rights concerning the solutions belong to them and no intellectual property rights of third parties are infringed. The European Commission accepts no responsibility with regard to these solutions.</p>
<p>It is our goal to minimize disruption caused by technical errors. However, some data or information on our site may have been created or structured in files or formats that are not error-free and we cannot guarantee that our service will not be interrupted or otherwise affected by such problems. The Commission accepts no responsibility with regard to such problems incurred as a result of using this site or any linked external sites.</p>
<p>This disclaimer is not intended to limit the liability of the Commission in contravention of any requirements laid down in applicable national law nor to exclude its liability for matters which may not be excluded under that law.</p>
<h2>Privacy Statement</h2>
<h3>The Specific electronic Service: Joinup</h3>
<p>The objective of this portal is to facilitate the development, sharing and re-use of interoperability solutions for public administrations as well as the sharing of best practices in domains relevant to the public sector.</p>
BODY;

  // Create the 'Legal notice' block.
  BlockContent::create([
    'type' => 'simple_block',
    'uuid' => 'ec092d17-ef18-42b0-b460-642871150cd3',
    'status' => TRUE,
    'info' => 'Legal notice',
    'body' => [
      'value' => $body,
      'format' => 'content_editor',
    ],
  ])->save();
}

/**
 * Install Entity Legal module.
 */
function joinup_post_update_legal() {
  // Dismantle the actual solution.
  /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
  $entity_repository = \Drupal::service('entity.repository');
  $block_content = $entity_repository->loadEntityByUuid('block_content', 'ec092d17-ef18-42b0-b460-642871150cd3');
  $block_content->delete();
  EntityFormDisplay::load('block_content.simple_block.default')->delete();
  EntityViewDisplay::load('block_content.simple_block.default')->delete();
  FieldConfig::loadByName('block_content', 'simple_block', 'body')->delete();
  FieldStorageConfig::loadByName('block_content', 'body')->delete();
  BlockContentType::load('simple_block')->delete();
  // Remove Page Manager data.
  PageVariant::load('legal_notice-block_display-0')->delete();
  Page::load('legal_notice')->delete();
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
  $module_installer = \Drupal::service('module_installer');
  $module_installer->uninstall([
    'block_content_permissions',
    'block_content',
    'page_manager',
  ]);

  drupal_flush_all_caches();

  $module_installer->install(['joinup_legal']);

  // Create initial content.
  EntityLegalDocumentVersion::create([
    'document_name' => 'legal_notice',
    'published' => TRUE,
    'label' => '1.1',
    'acceptance_label' => 'I have read and accept the <a href="[entity_legal_document:url]" target="_blank">[entity_legal_document:label]</a>',
    'entity_legal_document_text' => [
      'value' => '<h2>Important legal notice</h2>
<p>The information on this site is subject to a disclaimer, a copyright and rules related to personal data protection, each in line with the general <a href="http://ec.europa.eu/geninfo/legal_notices_en.htm">European Commission legal notice</a>, and terms of use.</p>
<h2>Copyright notice</h2>
<p>Unless otherwise indicated, reproduction is authorised, except for commercial purposes, provided that the source (Joinup) is acknowledged. Where prior permission must be obtained for the reproduction or use of textual and multimedia information (sound, images, software, etc.), such permission shall cancel the above-mentioned general permission and shall clearly indicate any restrictions on use.</p>
<h3>Special Rules for hosted and federated Open-Source Software projects</h3>
<p>Please note that all the Open-Source Applications (Projects), which are available through the repository on Joinup are provided by their owners (named in each case) subject to the copyright licences indicated in each case; the owners have to certify that all intellectual property rights concerning the Assets belong to them and no intellectual property rights of third parties are infringed. Please refer to the individual project for further information. Please note, that the European Commission accepts no responsibility with regard to these projects.</p>
<h3>Special Rules for interoperability solutions</h3>
<p>Reproduction is not authorized in general for the interoperability solutions. The copyright for the interoperability solutions is defined individually by the licence attached to the individual solution by its owner. Please refer to the individual solution for further information.</p>
<h2>Disclaimer</h2>
<p>The European Commission maintains this website to enhance public access to information about its initiatives and European Union policies in general. Our goal is to keep this information timely and accurate. If errors are brought to our attention, we will try to correct them. However, the Commission accepts no responsibility or liability whatsoever with regard to the information on this site.</p>
<p>This information is:</p>
<ol>
  <li>of a general nature only and is not intended to address the specific circumstances of any particular individual or entity;</li>
  <li>not necessarily comprehensive, complete, accurate or up to date; sometimes linked to external sites over which the Commission services have no control and for which the Commission assumes no responsibility;</li>
  <li>not professional or legal advice (if you need specific advice, you should always consult a suitably qualified professional).</li>
</ol>
<p>Please note that it cannot be guaranteed that a document available on-line exactly reproduces an officially adopted text. Only European Union legislation published in paper editions of the Official Journal of the European Union is deemed authentic.</p>
<p>Please also note that all interoperability solutions, which are available through the repository on Joinup are provided by their owners (named in each case) subject to the licences indicated in each case; the owners have to certify that all intellectual property rights concerning the solutions belong to them and no intellectual property rights of third parties are infringed. The European Commission accepts no responsibility with regard to these solutions.</p>
<p>It is our goal to minimize disruption caused by technical errors. However, some data or information on our site may have been created or structured in files or formats that are not error-free and we cannot guarantee that our service will not be interrupted or otherwise affected by such problems. The Commission accepts no responsibility with regard to such problems incurred as a result of using this site or any linked external sites.</p>
<p>This disclaimer is not intended to limit the liability of the Commission in contravention of any requirements laid down in applicable national law nor to exclude its liability for matters which may not be excluded under that law.</p>
<h2>Privacy Statement</h2>
<h3>The Specific Privacy Statement for the Joinup website can be found <a href="https://joinup.ec.europa.eu/sites/default/files/custom-page/attachment/2019-07/Specific_Privacy_Statement_Joinup_clean.pdf">here</a></h3>',
      'format' => 'content_editor',
    ],
  ])->save();
}

/**
 * Move statistics (part 1): Create metadata entities.
 */
function joinup_post_update_stats1(array &$sandbox): ?string {
  $entity_type_manager = \Drupal::entityTypeManager();
  $rdf_entity_storage = $entity_type_manager->getStorage('rdf_entity');
  $node_storage = $entity_type_manager->getStorage('node');

  if (!isset($sandbox['rdf_entity'])) {
    $sandbox['rdf_entity'] = $rdf_entity_storage->getQuery()
      ->condition('rid', 'asset_distribution')
      ->sort('id')
      ->execute();
    $sandbox['node'] = $node_storage->getQuery()
      ->condition('type', ['discussion', 'document', 'event', 'news'], 'IN')
      ->sort('nid')
      ->execute();
    $sandbox['processed_rdf_entity'] = 0;
    $sandbox['processed_node'] = 0;
  }

  if ($sandbox['rdf_entity'] && ($ids_to_process = array_splice($sandbox['rdf_entity'], 0, 30))) {
    foreach ($rdf_entity_storage->loadMultiple($ids_to_process) as $distribution) {
      MetaEntity::create([
        'type' => 'download_count',
        'target' => $distribution,
        'count' => $distribution->get('field_download_count'),
      ])->save();
      $sandbox['processed_rdf_entity']++;
    }
  }
  if ($sandbox['node'] && ($nids_to_process = array_splice($sandbox['node'], 0, 80))) {
    foreach ($node_storage->loadMultiple($nids_to_process) as $node) {
      MetaEntity::create([
        'type' => 'visit_count',
        'target' => $node,
        'count' => $node->get('field_visit_count'),
      ])->save();
      $sandbox['processed_node']++;
    }
  }
  $sandbox['#finished'] = empty($sandbox['rdf_entity']) && empty($sandbox['node']) ? 1 : 0;

  if ($sandbox['#finished']) {
    return "Finished processing {$sandbox['processed_rdf_entity']} distributions and {$sandbox['processed_node']} nodes.";
  }

  return "Progress: {$sandbox['processed_rdf_entity']} distributions, {$sandbox['processed_node']} nodes.";
}

/**
 * Move statistics (part 2): Cleanup obsolete configs.
 */
function joinup_post_update_stats2(): void {
  \Drupal::configFactory()->getEditable('joinup_core.matomo_settings')->delete();
}

/**
 * Move statistics (part 3): Uninstall stale fields.
 */
function joinup_post_update_stats3(): void {
  // Delete 'field_download_count' field.
  FieldConfig::loadByName('rdf_entity', 'asset_distribution', 'field_download_count')->delete();
  // Delete 'field_visit_count' field.
  foreach (['discussion', 'document', 'event', 'news'] as $bundle) {
    FieldConfig::loadByName('node', $bundle, 'field_visit_count')->delete();
  }
}

/**
 * Move statistics (part 4): Remove stale triples.
 */
function joinup_post_update_stats4(): void {
  /** @var \Drupal\Driver\Database\sparql\Connection $sparql_connection */
  $sparql_connection = \Drupal::service('sparql.endpoint');
  $sparql_connection->query("WITH <http://joinup.eu/asset_distribution/published>
DELETE {
  ?s ?p ?o .
}
WHERE {
  ?s ?p ?o .
  VALUES ?p { <http://schema.org/userInteractionCount> <http://schema.org/expires> }
}");
}

/**
 * Move statistics (part 5): Search API tasks.
 */
function joinup_post_update_stats5(): void {
  // The 'search_api.index.published' config entity should not be updated by
  // the configuration management, later in the flow, as changing this config
  // triggers a reindex but, unfortunately, because of the current config import
  // tools, it uses the obsolete config data. Instead, we partially disable the
  // reindex and import here the config entity. Later, in the update flow, we
  // always trigger a reindex.
  $config_data = Yaml::decode(file_get_contents(drupal_get_path('module', 'joinup_search') . '/config/install/search_api.index.published.yml'));
  $state = \Drupal::state();
  $state->set('search_api.index.published.has_reindexed', TRUE);
  \Drupal::configFactory()->getEditable('search_api.index.published')->setData($config_data)->save();
  $state->set('search_api.index.published.has_reindexed', FALSE);
}

/**
 * Move statistics (part 6): Update field queue.
 */
function joinup_post_update_stats6(array &$sandbox): ?string {
  $db = \Drupal::database();

  if (!isset($sandbox['items'])) {
    // Make sure that cron is not consuming the queue while we're updating it.
    // We try to acquire a lock, pretending that cron is running. If we succeed,
    // a cron that attempts to start after this update has started, will not run
    // as it will not be able to acquire the lock. If we fail, means the cron
    // has already started before this update. That should be reported, as all
    // cron processes should be stopped before starting the Joinup update.
    // @see \Drupal\Core\Cron::run()
    if (!\Drupal::lock()->acquire('cron', 900.0)) {
      // Cron is currently running.
      throw new \RuntimeException("Cannot update Joinup because cron is currently running. Ensure the conjob processes are stopped before attempting to update Joinup.");
    }
    $sandbox['items'] = [];
    $sandbox['processed'] = 0;

    // Store all 'cached_computed_field_expired_fields' queue items in sandbox.
    $items = $db->select('queue', 'q')
      ->fields('q', ['item_id', 'data'])
      ->condition('name', 'cached_computed_field_expired_fields')
      ->orderBy('q.item_id')
      ->execute()
      ->fetchAllKeyed();
    foreach ($items as $item_id => $data) {
      $data = unserialize($data);
      $sandbox['items'][] = [
        'id' => $item_id,
        'entity_id' => $data['entity_id'],
        'entity_type_id' => $data['entity_type'],
      ];
    }
  }

  if ($items_to_process = array_splice($sandbox['items'], 0, 500)) {
    $ids = array_map(function (array $item): string {
      return $item['entity_id'];
    }, $items_to_process);
    $entities = $db->select('meta_entity', 'm')
      ->fields('m', ['target__target_id', 'id'])
      ->condition('target__target_id', $ids, 'IN')
      ->execute()
      ->fetchAllKeyed();
    $items_to_delete = [];
    foreach ($items_to_process as $item) {
      if (isset($entities[$item['entity_id']])) {
        // References to content entities replaced with same to meta entities.
        $data = [
          'entity_type' => 'meta_entity',
          'entity_id' => $entities[$item['entity_id']],
          'field_name' => 'count',
          'expire' => 0,
        ];
        $db->update('queue')
          ->fields(['data' => serialize($data)])
          ->condition('item_id', $item['id'])
          ->execute();
      }
      else {
        // The entity might have been deleted in the meantime.
        $items_to_delete[] = $item['id'];
      }
      if ($items_to_delete) {
        $db->delete('queue')
          ->condition('item_id', $items_to_delete, 'IN')
          ->execute();
      }
      $sandbox['processed']++;
    }
  }

  $sandbox['#finished'] = $sandbox['items'] ? 0 : 1;

  if ($sandbox['#finished']) {
    // Release the fake 'cron' lock.
    \Drupal::lock()->release('cron');
    return "Processed {$sandbox['processed']} items from queue.";
  }

  return "Finished processing {$sandbox['processed']} items from queue.";
}

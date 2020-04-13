<?php

/**
 * @file
 * Post update functions for the custom_page module.
 */

declare(strict_types = 1);

use Drupal\node\Entity\Node;

/**
 * Update the custom pages URL aliases.
 */
function custom_page_post_update_aliases(array &$sandbox) {
  if (!isset($sandbox['current_nid'])) {
    // When deploying, the update and post-update processes are running before
    // the configuration storage is synchronized from the codebase. But for the
    // scope of this post-update script we need the Pathauto configuration
    // changes to be available, thus we are explicitly importing the changes.
    $config_factory = \Drupal::configFactory();
    $config_factory->getEditable('pathauto.settings')
      ->set('max_length', 255)
      ->save(TRUE);
    $config_factory->getEditable('pathauto.pattern.custom_page')
      ->set('pattern', 'collection/[node:og_audience]/[node:title]')
      ->save(TRUE);

    // Initialize the sandbox.
    $sandbox['current_nid'] = 0;
    $sandbox['count'] = 0;
  }

  $nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
    ->condition('type', 'custom_page')
    ->condition('status', TRUE)
    ->condition('nid', $sandbox['current_nid'], '>')
    ->sort('nid')
    ->range(0, 50)
    ->execute();

  if (!$nids) {
    $sandbox['#finished'] = 1;
    return t('Updated the path alias for @count published custom pages.', ['@count' => $sandbox['count']]);
  }

  /** @var \Drupal\pathauto\PathautoGeneratorInterface $pathauto_generator */
  $pathauto_generator = \Drupal::service('pathauto.generator');
  foreach (Node::loadMultiple($nids) as $nid => $custom_page) {
    $pathauto_generator->updateEntityAlias($custom_page, 'update');
    $sandbox['current_nid'] = $nid;
    $sandbox['count']++;
  }
  // Don't care about the progress value. We're only signaling here that the
  // process should continue with the next batch.
  $sandbox['#finished'] = 0.5;
}

/**
 * Rewrite menu links for improved performance.
 *
 * Rewrite 'internal' schemas to 'entity' and 'route' schemas: Menu links using
 * 'internal' get rebuild on each cache rebuild.
 *
 * @see \Drupal\Core\Url::fromUri()
 */
function custom_page_post_update_menu_links(array &$sandbox) {
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

  if (!isset($sandbox['current_id'])) {
    $sandbox['current_id'] = 0;
  }

  $mids = $storage->getQuery()
    ->condition('bundle', 'menu_link_content')
    ->condition('link.uri', "internal:/", "STARTS_WITH")
    ->condition('id', $sandbox['current_id'], '>')
    ->range(0, 200)
    ->sort('id', 'ASC')
    ->execute();
  $sandbox['#finished'] = FALSE;
  if (empty($mids)) {
    $sandbox['#finished'] = TRUE;
    return;
  }

  /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
  foreach ($storage->loadMultiple($mids) as $menu_link) {
    $sandbox['current_id'] = $menu_link->id();
    $link = $menu_link->get('link');
    $uri = $link->first()->getValue()['uri'];

    // Only act on wrong written entity paths.
    if (!preg_match('#^internal:/(rdf_entity|node)/#', $uri)) {
      continue;
    }

    // Rewrite the canonical entity paths to use the entity schema.
    if (substr_count($uri, '/') === 2) {
      $new_uri = str_replace('internal:/', 'entity:', $uri);
    }
    // Rewrite the members paths to its route based schema.
    elseif (preg_match('#^internal:/(rdf_entity|node)/([^/].*)/members#', $uri)) {
      $new_uri = str_replace('/members', '', $uri);
      $new_uri = str_replace('internal:/rdf_entity/', 'route:entity.rdf_entity.member_overview;rdf_entity=', $new_uri);
    }
    // Rewrite the about paths to its route based schema.
    elseif (preg_match('#^internal:/(rdf_entity|node)/([^/].*)/about#', $uri)) {
      $new_uri = str_replace('/about', '', $uri);
      $new_uri = str_replace('internal:/rdf_entity/', 'route:entity.rdf_entity.about_page;rdf_entity=', $new_uri);
    }
    else {
      continue;
    }

    $menu_link->set('link', $new_uri)->save();
  }
}

/**
 * Remove the "Menu sub pages" block.
 */
function custom_page_post_update_delete_sub_pages_menu_block() {
  $block = \Drupal::entityTypeManager()->getStorage('block')->load('menusubpages');
  if ($block) {
    $block->delete();
  }
}

/**
 * Provide default 'global_search' value property.
 */
function custom_page_post_update_default_global_search(array &$sandbox): string {
  $db = \Drupal::database();

  // Using direct databases queries rather than the API for performance reasons.
  if (!isset($sandbox['rows'])) {
    $sandbox['rows'] = [];
    foreach (['node__field_cp_content_listing', 'node_revision__field_cp_content_listing'] as $table) {
      $rows = $db->select($table)
        ->fields($table, ['revision_id', 'field_cp_content_listing_value'])
        ->condition('field_cp_content_listing_value', '%"global_search"%', 'NOT LIKE')
        ->execute()
        ->fetchAllKeyed();
      foreach ($rows as $revision_id => $value) {
        $sandbox['rows'][] = [
          'revision_id' => $revision_id,
          'table' => $table,
          'value' => unserialize($value),
        ];
      }
    }
    $sandbox['total'] = count($rows);
  }

  $rows = array_splice($sandbox['rows'], 0, 300);
  foreach ($rows as $row) {
    $row['value']['global_search'] = FALSE;
    $db->update($row['table'])
      ->fields(['field_cp_content_listing_value' => serialize($row['value'])])
      ->condition('revision_id', $row['revision_id'])
      ->execute();
  }
  $sandbox['#finished'] = (int) empty($sandbox['rows']);

  return 'Processed ' . count($rows) . ' items out of ' . $sandbox['total'];
}

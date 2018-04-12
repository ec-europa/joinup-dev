<?php

/**
 * @file
 * Post update functions for the custom_page module.
 */

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
 * Rewrite 'internal' schemas to 'entity' and 'route' schemas:
 * Menu links using 'internal' get rebuild on each cache rebuild.
 *
 * @see \Drupal\Core\Url::fromUri
 */
function custom_page_post_update_menu_links(&$sandbox) {
  if (!isset($sandbox['current_id'])) {
    $sandbox['current_id'] = 0;
  }
  $mids = \Drupal::entityTypeManager()
    ->getStorage('menu_link_content')
    ->getQuery()
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
  $menu_links = \Drupal::entityTypeManager()
    ->getStorage('menu_link_content')
    ->loadMultiple($mids);
  /** @var \Drupal\menu_link_content\MenuLinkContentInterface $menu_link */
  foreach ($menu_links as $menu_link) {
    $sandbox['current_id'] = $menu_link->id();
    $link = $menu_link->get('link');
    $uri = $link->first()->getValue()['uri'];
    // Only act on wrong written entity paths.
    if (preg_match('/internal:\/(rdf_entity|node)\//', $uri) !== 1) {
      continue;
    }
    // Rewrite the canonical entity paths to use the entity schema.
    if (substr_count($uri, '/') === 2) {
      $new_uri = str_replace('internal:/', 'entity:', $uri);
      $menu_link->set('link', $new_uri);
    }
    // Rewrite the members paths to its route based schema.
    elseif (preg_match('/internal:\/(rdf_entity|node)\/(.*)\/members/', $uri) === 1) {
      $new_uri = str_replace('/members', '', $uri);
      $new_uri = str_replace('internal:/rdf_entity/', 'route:entity.rdf_entity.member_overview;rdf_entity=', $new_uri);
      $menu_link->set('link', $new_uri);
    }
    // Rewrite the about paths to its route based schema.
    elseif (preg_match('/internal:\/(rdf_entity|node)\/(.*)\/about/', $uri) === 1) {
      $new_uri = str_replace('/about', '', $uri);
      $new_uri = str_replace('internal:/rdf_entity/', 'route:entity.rdf_entity.about_page;rdf_entity=', $new_uri);
      $menu_link->set('link', $new_uri);
    }
    $menu_link->save();
  }
}

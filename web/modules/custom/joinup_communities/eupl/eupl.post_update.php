<?php

/**
 * @file
 * Post update functions for EUPL module.
 */

declare(strict_types = 1);

use Drupal\node\Entity\Node;

/**
 * Move the JLA custom page under JLA solution.
 */
function eupl_post_update_jla_move(): void {
  // Move the node under JLA solution.
  Node::load(701805)
    ->set('og_audience', 'http://data.europa.eu/w21/0b18c781-6c88-41ad-b986-63a184693725')
    ->save();
  // Sink the left side menu link deep under the standard solution links.
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $ids = $storage->getQuery()
    ->condition('link.uri', 'entity:node/701805')
    ->execute();
  $id = reset($ids);
  $storage->load($id)->set('weight', 100)->save();
}

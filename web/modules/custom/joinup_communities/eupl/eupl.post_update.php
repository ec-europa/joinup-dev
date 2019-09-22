<?php

/**
 * @file
 * Post update functions for EUPL module.
 */

declare(strict_types = 1);

use Drupal\eupl\Eupl;
use Drupal\node\Entity\Node;

/**
 * Move the JLA custom page under JLA solution.
 */
function eupl_post_update_jla_move(): void {
  // Move the node under JLA solution.
  Node::load(701805)
    ->set('og_audience', Eupl::JLA_SOLUTION)
    ->save();
  // Sink the left side menu link deep under the standard solution links.
  $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');
  $ids = $storage->getQuery()
    ->condition('link.uri', 'entity:node/701805')
    ->execute();
  $id = reset($ids);
  $storage->load($id)->set('weight', 100)->save();
}

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

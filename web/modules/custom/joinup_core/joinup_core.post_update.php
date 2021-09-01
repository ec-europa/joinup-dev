<?php

/**
 * @file
 * Post update functions for Joinup.
 *
 * This should only contain update functions that rely on the Drupal API but
 * need to run _before_ the configuration is imported.
 *
 * For example this can be used to enable a new module that needs to have its
 * code available for the configuration to be successfully imported or updated.
 *
 * In most cases though update code should be placed in joinup_core.deploy.php.
 */

declare(strict_types = 1);

/**
 * Update existing custom pages if no filters or query is set.
 */
function joinup_core_post_update_0107500(&$sandbox): string {
  if (empty($sandbox['ids'])) {
    $sandbox['ids'] = \Drupal::entityQuery('node')->condition('type', 'custom_page')->execute();
    $sandbox['count'] = 0;
    $sandbox['max'] = count($sandbox['ids']);
  }

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $nids = array_splice($sandbox['ids'], 0, 50);
  foreach ($node_storage->loadMultiple($nids) as $entity) {
    // Avoid saving an entity that had no changes.
    $save = FALSE;
    if ($entity->hasField('field_paragraphs_body')) {
      $elements = $entity->get('field_paragraphs_body');
      for ($i = 0; $i < $elements->count(); $i++) {
        $paragraph = \Drupal::entityTypeManager()->getStorage('paragraph')->load($elements->get($i)->target_id);
        if ($paragraph->bundle() == 'content_listing') {
          $values = $paragraph->field_content_listing->value;
          if (empty($values['query_presets']) && !array_key_exists('query_builder', $values)) {
            $elements->removeItem($i);
            // Caution: decrement the counter as removeItem()
            // also does a rekey().
            $i--;
            $save = TRUE;
          }
        }
      }
    }

    if ($save) {
      // Do not send emails for these changes.
      $entity->skip_notification = 1;
      $entity->save();
    }
    $sandbox['count']++;
  }

  $sandbox['#finished'] = $sandbox['count'] === $sandbox['max'];
  return "Updated {$sandbox['count']} out of {$sandbox['max']} custom pages.";
}

/**
 * Update the index datasources before the search API updates the index.
 */
function joinup_core_post_update_0107501(&$sandbox) {
  // Search API updates the dependencies for each index. This creates conflicts
  // with the configuration update since we change the datasources
  // configuration. Manually update the index before the configuration.
  //
  // @see search_api_post_update_fix_index_dependencies
  /** @var \Drupal\search_api\IndexInterface $index */
  $search_api_index = \Drupal::entityTypeManager()->getStorage('search_api_index')->load('published');

  $node_datasource = $search_api_index->getDatasource('entity:node');
  $configuration = $node_datasource->getConfiguration();
  $index = array_search('newsletter', $configuration['bundles']['selected']);
  if ($index !== FALSE) {
    unset($configuration['bundles']['selected'][$index]);
  }
  $node_datasource->setConfiguration($configuration);

  $taxonomy_datasource = $search_api_index->getDatasource('entity:taxonomy_term');
  $configuration = $taxonomy_datasource->getConfiguration();
  // The topic is already the last in the list so we don't need to sort.
  $configuration['bundles']['selected'][] = 'topic';
  $taxonomy_datasource->setConfiguration($configuration);

  $search_api_index->save();
}

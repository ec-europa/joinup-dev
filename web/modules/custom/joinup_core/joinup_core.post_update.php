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
 * Update the index datasources before the search API updates the index.
 */
function joinup_core_post_update_0107500(&$sandbox) {
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

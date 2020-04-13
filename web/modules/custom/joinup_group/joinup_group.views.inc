<?php

/**
 * @file
 * Views integration for Joinup Group module.
 */

declare(strict_types = 1);

/**
 * Implements hook_views_data_alter().
 */
function joinup_group_views_data_alter(array &$data): void {
  // An RDF entity ID is passed as URL argument with its encoded-URL-safe,
  // value. Wrap the Search API argument plugin, in order to decode the value.
  $data['search_api_index_published']['entity_groups']['argument']['id'] = 'search_api_group';
  $data['search_api_index_unpublished']['entity_groups']['argument']['id'] = 'search_api_group';
  $data['node__og_audience']['og_audience_target_id']['argument']['id'] = 'og_audience_group';
}

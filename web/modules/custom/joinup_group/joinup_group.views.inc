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
  // Allow the Search API 'entity_bundle' field to be displayed as its label.
  $data['search_api_index_published']['entity_bundle']['field']['id'] = 'joinup_search_api_entity_bundle';
  $data['search_api_index_unpublished']['entity_bundle']['field']['id'] = 'joinup_search_api_entity_bundle';
  // Allow the Search API 'entity_bundle' field to be filtered with a select.
  $data['search_api_index_published']['entity_bundle']['filter']['id'] = 'joinup_search_api_entity_bundle';
  $data['search_api_index_unpublished']['entity_bundle']['filter']['id'] = 'joinup_search_api_entity_bundle';
  // Allow the Search API timestamp fields to be displayed as a date/time.
  $data['search_api_index_published']['entity_created']['field']['id'] = 'joinup_search_api_timestamp';
  $data['search_api_index_unpublished']['entity_created']['field']['id'] = 'joinup_search_api_timestamp';
  $data['search_api_index_published']['entity_changed']['field']['id'] = 'joinup_search_api_timestamp';
  $data['search_api_index_unpublished']['entity_changed']['field']['id'] = 'joinup_search_api_timestamp';
}

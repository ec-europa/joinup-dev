<?php

/**
 * @file
 * Post update functions for the collection module.
 */

/**
 * Updates the collection content field to show the events facet.
 */
function collection_post_update_show_events_facet() {
  /** @var \Drupal\rdf_entity\RdfInterface[] $collections */
  $collections = \Drupal::entityTypeManager()->getStorage('rdf_entity')->loadByProperties(['rid' => 'collection']);
  foreach ($collections as $collection) {
    $value = $collection->get('field_collection_content')->getValue();
    $value[0]['value']['fields'] += [
      'collection_event_type' => [
        'weight' => -1,
        'region' => 'inline_facets',
      ],
    ];

    $collection->set('field_collection_content', $value);
    $collection->skip_notification = TRUE;
    $collection->save();
  }
}

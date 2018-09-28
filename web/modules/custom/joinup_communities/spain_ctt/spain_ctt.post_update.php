<?php

/**
 * @file
 * Post update functions for the Spain CTT module.
 */

/**
 * Clean more solution duplicates in the ctt collection.
 */
function spain_ctt_post_update_clean_ctt_duplicates() {
  // The list of entries to be cleaned has been updated. We only need to call
  // the install function again.
  require __DIR__ . '/spain_ctt.install';
  spain_ctt_install();
}

/**
 * Enable pipeline, joinup_federation and rdf_entity_provenance modules.
 */
function spain_ctt_post_update_enable_pipeline_modules() {
  \Drupal::service('module_installer')->install(['joinup_federation']);
}

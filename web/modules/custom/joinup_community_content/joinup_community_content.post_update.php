<?php

/**
 * @file
 * Post update functions for the Joinup Community Content module.
 */

/**
 * Enable the Changed Fields API module.
 */
function joinup_community_content_post_update_enable_subpathauto() {
  \Drupal::service('module_installer')->install(['changed_fields']);
}

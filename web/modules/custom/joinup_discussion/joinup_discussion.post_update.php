<?php

/**
 * @file
 * Post update functions for the Joinup Discussion module.
 */

/**
 * Enable the Changed Fields API module.
 */
function joinup_discussion_post_update_enable_changed_fields() {
  \Drupal::service('module_installer')->install(['changed_fields']);
}

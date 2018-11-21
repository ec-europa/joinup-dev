<?php

/**
 * @file
 * Post update functions for the Joinup profile.
 */

/**
 * Enable the "Views data export" module.
 */
function joinup_post_update_install_views_data_export() {
  \Drupal::service('module_installer')->install(['views_data_export']);
}

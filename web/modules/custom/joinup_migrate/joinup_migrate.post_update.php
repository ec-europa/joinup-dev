<?php

/**
 * @file
 * Post update functions for Joinup Migrate.
 */

/**
 * Disable the Update module.
 */
function joinup_migrate_post_update_disable_update() {
  \Drupal::service('module_installer')->uninstall(['update']);
}

<?php

/**
 * @file
 * Post update functions for the Joinup core module.
 */

/**
 * Enable the Sub-Pathauto module.
 */
function joinup_core_post_update_enable_subpathauto() {
  \Drupal::service('module_installer')->install(['subpathauto']);
}

<?php

/**
 * @file
 * Post update functions for Joinup Subscription module.
 */

/**
 * Install the 'flag' module.
 */
function joinup_subscription_post_update_install_flag() {
  \Drupal::service('module_installer')->install(['flag']);
}

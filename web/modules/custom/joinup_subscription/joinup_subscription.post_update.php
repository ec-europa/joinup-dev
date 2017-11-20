<?php

/**
 * @file
 * Post update functions for the Joinup subscription module.
 */

/**
 * Install the message_subscribe module.
 */
function joinup_subscription_post_update_install_message_subscribe() {
  \Drupal::service('module_installer')->install(['message_subscribe']);
}

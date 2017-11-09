<?php

/**
 * @file
 * Post update functions for the Joinup subscription module.
 */

use Drupal\flag\Entity\Flag;

/**
 * Install the message_subscribe module and delete its default flags.
 */
function joinup_subscription_post_update_install_message_subscribe() {
  \Drupal::service('module_installer')->install(['message_subscribe']);

  $flag_ids = [
    'subscribe_og',
    'subscribe_node',
    'subscribe_term',
    'subscribe_user',
  ];
  foreach ($flag_ids as $flag_id) {
    Flag::load($flag_id)->delete();
  }
}

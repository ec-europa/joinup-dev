<?php

/**
 * @file
 * Post update functions for the Joinup subscription module.
 */

use Drupal\flag\Entity\Flag;

/**
 * Delete the default flags created by the message_subscribe module.
 */
function joinup_subscription_post_update_delete_default_flags() {
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

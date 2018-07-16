<?php

/**
 * @file
 * Post update functions for Tallinn.
 */

/**
 * Install the 'embed_block' module.
 */
function tallinn_post_update_install_embed_block() {
  \Drupal::service('module_installer')->install(['embed_block']);
}

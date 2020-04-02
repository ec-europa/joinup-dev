<?php

/**
 * @file
 * Contains post update functions for the joinup_video module.
 */

declare(strict_types = 1);

/**
 * Enforce the privacy mode for providers that support it.
 */
function joinup_video_post_update_set_video_privacy_mode() {
  \Drupal::service('config.factory')
    ->getEditable('video_embed_field.settings')
    ->set('privacy_mode', 'enabled')
    ->save();
}

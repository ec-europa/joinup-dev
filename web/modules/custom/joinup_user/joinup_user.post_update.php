<?php

/**
 * @file
 * Post update functions for the Joinup User module.
 */

use Drupal\user\UserInterface;

/**
 * Adds permissions to subscribe to discussions for authenticated users.
 */
function joinup_user_post_update_add_subscribe_discussion_permissions() {
  user_role_grant_permissions(UserInterface::AUTHENTICATED_ROLE, [
    'flag subscribe_discussions',
    'unflag subscribe_discussions',
  ]);
}

/**
 * Add the 'access joinup reports' permission to moderators and administrators.
 */
function joinup_user_post_update_joinup_reports() {
  foreach (['moderator', 'administrator'] as $rid) {
    user_role_grant_permissions($rid, ['access joinup reports']);
  }
}

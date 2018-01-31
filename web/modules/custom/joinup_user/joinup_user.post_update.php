<?php

/**
 * @file
 * Post update functions for the Joinup user module.
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
 * Allow authenticated users to manage their subscriptions.
 */
function joinup_user_post_update_access_dashboard() {
  user_role_grant_permissions(UserInterface::AUTHENTICATED_ROLE, [
    'manage own subscriptions',
  ]);
}

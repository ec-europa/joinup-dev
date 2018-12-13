<?php

/**
 * @file
 * Post update functions for the Joinup User module.
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;
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

/**
 * Remove configuration for an e-mail that has been replaced by a Message.
 */
function joinup_user_post_update_remove_obsolete_og_roles_changed_message_config() {
  // This was originally a regular 'hook_mail()' message but it has been
  // converted into the 'og_membership_role_change' Message template. The
  // original config is no longer used and can be removed.
  $config = \Drupal::configFactory()->getEditable('joinup_user.mail');
  $config->clear('og_roles_changed')->save();
}

/**
 * Grant the authenticated users the 'never autoplay videos' permission.
 */
function joinup_user_post_update_add_never_autoplay_permission() {
  user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['never autoplay videos']);
}

/**
 * Remove the permissions to manage subscriptions.
 */
function joinup_user_remove_subscription_permissions() {
  // Separate permissions are not needed for this since the subscription
  // settings are part of the user profile. It is fully covered by the
  // 'administer users' permission, and users are always allowed to edit their
  // own profiles.
  /** @var \Drupal\user\RoleInterface $role */
  foreach (Role::loadMultiple() as $role) {
    foreach (['manage own permissions', 'manage all permissions'] as $permission) {
      $role->revokePermission($permission);
    }
    $role->save();
  }
}

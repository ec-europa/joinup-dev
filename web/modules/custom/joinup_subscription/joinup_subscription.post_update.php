<?php

/**
 * @file
 * Post update functions for Joinup Subscription module.
 */

use Drupal\og\Entity\OgMembership;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Install the 'flag' module.
 */
function joinup_subscription_post_update_install_flag() {
  \Drupal::service('module_installer')->install(['flag']);
}

/**
 * Update all OG memberships with being subscribed to community content.
 */
function joinup_subscription_post_update_subscribe_to_community_content(&$sandbox) {
  if (!isset($sandbox['total'])) {
    $sandbox['total'] = \Drupal::entityQuery('og_membership')->count()->execute();
    $sandbox['current'] = 0;
  }

  $memberships_per_batch = 1000;
  $mids = \Drupal::entityQuery('og_membership')
    ->range($sandbox['current'], $memberships_per_batch)
    ->execute();

  foreach (OgMembership::loadMultiple($mids) as $membership) {
    // Organic Groups can be configured to delete memberships asynchronously in
    // a cron job or a manually started batch process. This means orphaned
    // memberships can be present in the database. Ignore them.
    if (empty($membership->getGroup())) {
      continue;
    }
    $membership->set('subscription_bundles', [
      ['entity_type' => 'node', 'bundle' => 'discussion'],
      ['entity_type' => 'node', 'bundle' => 'document'],
      ['entity_type' => 'node', 'bundle' => 'event'],
      ['entity_type' => 'node', 'bundle' => 'news'],
    ])->save();
  }

  $count = count($mids);
  $sandbox['current'] += $count;
  $sandbox['#finished'] = $count < $memberships_per_batch ? 1 : $sandbox['current'] / $sandbox['total'];

  return $sandbox['current'] . ' memberships updated.';
}

/**
 * Remove the obsolete subscription fields on the user entity.
 */
function joinup_subscription_post_update_remove_group_types_field() {
  // In an earlier implementation of the group content subscriptions the user
  // was able to subscribe globally to all the groups they are a member of. This
  // preference was saved in fields on the user entity. It has been replaced by
  // a per-membership subscription, so the original fields can be removed. There
  // is no migration planned since these field were present but unused.
  foreach (['field_user_group_types', 'field_user_subscription_events'] as $field_name) {
    $field_config = FieldConfig::loadByName('user', 'user', $field_name);
    if (!empty($field_config)) {
      $field_config->delete();
    }
    $field_storage_config = FieldStorageConfig::loadByName('user', $field_name);
    if (!empty($field_storage_config)) {
      $field_storage_config->delete();
    }
  }
}

<?php

/**
 * @file
 * Post update functions for Joinup Subscription module.
 */

use Drupal\og\Entity\OgMembership;

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

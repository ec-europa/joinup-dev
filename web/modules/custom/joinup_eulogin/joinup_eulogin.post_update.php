<?php

/**
 * @file
 * Post-update scripts for Joinup EU Login module.
 */

declare(strict_types = 1);

use Drupal\user\Entity\User;

/**
 * Replace the password with a random hash for existing EU Login linked users.
 */
function joinup_eulogin_post_update_set_random_passwords(array &$sandbox): string {
  if (!isset($sandbox['uids'])) {
    $sandbox['uids'] = \Drupal::database()
      ->select('authmap')
      ->fields('authmap', ['uid'])
      ->condition('provider', 'cas')
      ->orderBy('uid')
      ->execute()
      ->fetchCol();
    $sandbox['total'] = count($sandbox['uids']);
    $sandbox['progress'] = 0;
  }

  $uids_to_process = \array_splice($sandbox['uids'], 0, 50);
  /** @var \Drupal\user\UserInterface $account */
  foreach (User::loadMultiple($uids_to_process) as $account) {
    $account->setPassword(\user_password(30))->save();
    $sandbox['progress']++;
  }
  $sandbox['#finished'] = (int) empty($sandbox['uids']);

  return "Finished processing {$sandbox['progress']} out of {$sandbox['total']} accounts.";
}

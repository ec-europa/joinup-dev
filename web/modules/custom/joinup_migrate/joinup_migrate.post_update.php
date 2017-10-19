<?php

/**
 * @file
 * Post update functions for Joinup Migrate module.
 */

use Drupal\Core\Database\Database;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate_run\MigrateExecutable;
use Drupal\node\Entity\Node;

/**
 * Add the missed 'simatosc' user (uid 73932).
 */
function joinup_migrate_post_update_add_user_73932() {
  $uid = 73932;

  // Make this user eligible for migration.
  Database::getConnection('default', 'migrate')
    ->update('userpoints')
    ->fields(['points' => 10])
    ->condition('uid', $uid)
    ->execute();

  /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager */
  $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
  $message = new MigrateMessage();

  $migrations = $migration_plugin_manager->createInstances([
    'user',
    'file:user_photo',
    'user_profile',
  ]);
  // Run user migrations.
  foreach ($migrations as $migration_id => $migration) {
    $migration->set('requirements', []);
    (new MigrateExecutable($migration, $message, ['idlist' => $uid]))
      ->import();
  }
  // Run solution OG membership migration.
  $migration = $migration_plugin_manager->createInstance('og_user_role_solution')
    ->set('requirements', []);
  (new MigrateExecutable($migration, $message, ['idlist' => "157750:$uid"]))
    ->import();

  // Fix the authorship for two 'news' nodes.
  foreach (Node::loadMultiple([165260, 164381]) as $node) {
    /** @var \Drupal\node\NodeInterface $node */
    $node->setOwnerId($uid)->save();
  }
  // Fix the authorship for several files.
  $fids = [33857, 33858, 33859, 33860, 33861, 33862, 33863];
  foreach (File::loadMultiple($fids) as $file) {
    /** @var \Drupal\file\FileInterface $file */
    $file->setOwnerId($uid)->save();
  }
}

/**
 * Disable the Update module.
 */
function joinup_migrate_post_update_disable_update() {
  \Drupal::service('module_installer')->uninstall(['update']);
}

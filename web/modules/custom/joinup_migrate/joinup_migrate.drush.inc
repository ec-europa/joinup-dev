<?php

/**
 * @file
 * Drush commands for Joinup Migrate module.
 */

use Drupal\Core\Database\Database;
use Drupal\joinup_migrate\MockFileSystem;

/**
 * Implements hook_drush_command().
 */
function joinup_migrate_drush_command() {
  return [
    'mock-file-system' => [
      'description' => 'Mock the directory structure of file to be imported for testing reasons.',
      'aliases' => ['mkfs'],
    ],
  ];
}

/**
 * Mocks the directory structure of file to be imported for testing reasons.
 */
function drush_joinup_migrate_mock_file_system() {
  $public_directory = \Drupal::service('stream_wrapper.public')->getDirectoryPath();
  $base_dir = "$public_directory/joinup_migrate/webroot";
  $db = Database::getConnection('default', 'migrate');

  MockFileSystem::createTestingFiles($base_dir, $db);
}

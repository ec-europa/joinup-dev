<?php

namespace Drupal\joinup_migrate;

use Drupal\Core\Database\Connection;

/**
 * Helper method to create a fake file system to import from (used for testing).
 */
class MockFileSystem {

  /**
   * Recreates the source file system structure and files with 0 size.
   *
   * Used for testing purposes.
   *
   * @param string $base_dir
   *   The base dir for the filesystem  mock.
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection from where to read the data. It's the source DB.
   */
  public static function createTestingFiles($base_dir, Connection $db) {
    $files = [];

    // Add 'discussion' attachments.
    $files = array_merge(
      $files,
      $db->select('d8_file_discussion', 'a')
        ->fields('a', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'event' attachments.
    $files = array_merge(
      $files,
      $db->select('d8_file_event', 'a')
        ->fields('a', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'news' attachments.
    $files = array_merge(
      $files,
      $db->select('d8_file_news', 'a')
        ->fields('a', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'collection' logos.
    $files = array_merge(
      $files,
      $db->select('d8_collection', 'c')
        ->fields('c', ['logo'])
        ->isNotNull('c.logo')
        ->condition('c.logo', '../resources/migrate/collection/logo/%', 'NOT LIKE')
        ->execute()
        ->fetchCol()
    );

    // Add 'comment' files.
    $files = array_merge(
      $files,
      $db->select('d8_comment_file', 'f')
        ->fields('f', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'distribution' files.
    $files = array_merge(
      $files,
      $db->select('d8_distribution', 'd')
        ->fields('d', ['file_path'])
        ->isNotNull('d.file_path')
        ->execute()
        ->fetchCol()
    );

    // Add 'document' files.
    $files = array_merge(
      $files,
      $db->select('d8_document_file', 'df')
        ->fields('df', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'documentation' files.
    $files = array_merge(
      $files,
      $db->select('d8_documentation_file', 'df')
        ->fields('df', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'event' logos.
    $files = array_merge(
      $files,
      $db->select('d8_event', 'e')
        ->fields('e', ['file_path'])
        ->isNotNull('e.file_path')
        ->condition('e.file_path', '', '<>')
        ->execute()
        ->fetchCol()
    );

    // Add 'solution' logos.
    $files = array_merge(
      $files,
      $db->select('d8_solution', 's')
        ->fields('s', ['logo'])
        ->isNotNull('s.logo')
        ->condition('s.logo', '../resources/migrate/solution/logo/%', 'NOT LIKE')
        ->execute()
        ->fetchCol()
    );

    // Add 'user' photos.
    $files = array_merge(
      $files,
      $db->select('d8_user', 'u')
        ->fields('u', ['photo_path'])
        ->isNotNull('u.photo_path')
        ->condition('u.photo_path', '', '<>')
        ->execute()
        ->fetchCol()
    );

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    foreach ($files as $file) {
      $path = $base_dir . '/' . pathinfo($file, PATHINFO_DIRNAME);
      $file_name = pathinfo($file, PATHINFO_BASENAME);
      if (!is_dir($path)) {
        $file_system->mkdir($path, NULL, TRUE);
      }
      $file_path = "$path/$file_name";
      if (!file_exists($file_path)) {
        // Create a '0 size' file.
        touch($file_path);
      }
    }
  }

}

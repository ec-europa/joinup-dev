<?php

namespace Drupal\joinup_migrate;

use Drupal\Core\Database\Database;

/**
 * Helper method to create a fake file system to import from (used for testing).
 */
class MockFileSystem {

  /**
   * Recreates the source file system structure and files with 0 size.
   *
   * Used for testing purposes.
   *
   * @return string
   *   The absolute path to the mocked file system.
   */
  public static function createTestingFiles() {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $public_directory = \Drupal::service('stream_wrapper.public')->getDirectoryPath();
    $base_dir = "$public_directory/joinup_migrate/files";
    $db = Database::getConnection('default', 'migrate');
    $tables = $db->query("SHOW TABLES LIKE 'd8\_file\_%'")->fetchCol();

    $files = [];
    foreach ($tables as $table) {
      $files = array_merge(
        $files,
        array_filter($db->select($table)
          ->fields($table, ['path'])
          ->execute()
          ->fetchCol(), function ($file) {
            return !empty($file) && (strpos($file, '../') !== 0);
          }
        )
      );
    }

    // Delete the base dir, if exists.
    if (is_dir($base_dir)) {
      file_unmanaged_delete_recursive($base_dir);
    }

    foreach ($files as $file) {
      $path_parts = pathinfo($file);
      $path = $base_dir;
      if ($path_parts['dirname'] !== '.') {
        $path .= '/' . $path_parts['dirname'];
      }
      if (!is_dir($path)) {
        $file_system->mkdir($path, NULL, TRUE);
      }
      $file_name = $path_parts['basename'];
      $file_path = "$path/$file_name";
      if (!file_exists($file_path)) {
        // Create a '0 size' file.
        touch($file_path);
      }
    }

    return $file_system->realpath($base_dir);
  }

}

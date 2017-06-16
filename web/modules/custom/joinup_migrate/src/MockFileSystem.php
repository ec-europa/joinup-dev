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
    $tables = [
      'd8_file_collection_logo',
      'd8_file_comment_attachment',
      'd8_file_custom_page_attachment',
      'd8_file_discussion_attachment',
      'd8_file_distribution',
      'd8_file_document_case',
      'd8_file_document_document',
      'd8_file_document_factsheet',
      'd8_file_document_presentation',
      'd8_file_documentation_release',
      'd8_file_documentation_solution',
      'd8_file_event_attachment',
      'd8_file_event_logo',
      'd8_file_news_attachment',
      'd8_file_solution_logo',
      'd8_file_user_photo',
    ];

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

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

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
  }

}

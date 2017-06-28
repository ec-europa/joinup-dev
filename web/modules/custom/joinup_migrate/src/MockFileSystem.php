<?php

namespace Drupal\joinup_migrate;

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
    /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager */
    $migration_manager = \Drupal::service('plugin.manager.migration');

    $public_directory = \Drupal::service('stream_wrapper.public')->getDirectoryPath();
    $base_dir = "$public_directory/joinup_migrate/files";
    // Delete the base dir, if exists.
    if (is_dir($base_dir)) {
      file_unmanaged_delete_recursive($base_dir);
    }

    $files = [];
    foreach ($migration_manager->createInstances([]) as $migration) {
      if ($migration->getBaseId() === 'file') {
        /** @var \Drupal\migrate\Plugin\migrate\source\SourcePluginBase $source */
        $source = $migration->getSourcePlugin();
        $source->rewind();
        while ($source->valid()) {
          $row = $source->current();
          $file = $row->getSourceProperty('path');
          if (strpos($file, '../') !== 0) {
            // Make relative path.
            $length = strlen(FileUtility::getLegacySiteFiles());
            $files[] = substr($file, $length);
          }
          $source->next();
        }
      }
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

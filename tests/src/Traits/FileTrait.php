<?php

namespace Drupal\joinup\Traits;

use Drupal\file\Entity\File;

/**
 * Helper methods for dealing with files.
 */
trait FileTrait {

  /**
   * Test files.
   *
   * @var \Drupal\file\Entity\File[]
   */
  protected $files = [];

  /**
   * Saves a file for an entity and returns the file's ID.
   *
   * @param string $filename
   *   The file name given by the user.
   * @param string $files_path
   *   The path of the directory where the file is located. Defaults to the path
   *   configured in the 'files_path' parameter in behat.yml.
   *
   * @return \Drupal\file\Entity\File
   *   The file.
   *
   * @throws \Exception
   *   Throws an exception when the file is not found.
   */
  protected function createFile($filename, $files_path = NULL) {
    // Default to the 'files_path' mink parameter defined in behat.yml.
    if (empty($files_path)) {
      $files_path = $this->getMinkParameter('files_path');
    }
    $path = rtrim(realpath($files_path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path)) {
      throw new \Exception("File '$filename' was not found in file path '$files_path'.");
    }
    // Copy the file into the public files folder and turn it into a File
    // entity before linking it to the collection.
    $uri = 'public://' . $filename;
    $destination = file_unmanaged_copy($path, $uri);
    $file = File::create(['uri' => $destination]);
    $file->save();

    $this->files[$file->id()] = $file;

    return $file;
  }

  /**
   * Remove any created files.
   *
   * @AfterScenario
   */
  public function cleanFiles() {
    // Remove the image entities that were attached to the collections.
    foreach ($this->files as $file) {
      $file->delete();
    }
  }

}

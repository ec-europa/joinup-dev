<?php

namespace Drupal\joinup\Traits;

use Drupal\file\Entity\File;
use Drupal\rdf_file\RdfFileHandler;

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
   *   The file path where the file exists in.
   *
   * @return int
   *   The file ID returned by the File::save() method.
   *
   * @throws \Exception
   *   Throws an exception when the file is not found.
   */
  protected function createFile($filename, $files_path) {
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

    return $file->id();
  }

  /**
   * Uploads a RDF file for an entity and returns the file's ID.
   *
   * @param string $filename
   *   The file name given by the user.
   * @param string $files_path
   *   The file path where the file exists in.
   *
   * @return string
   *   The file ID.
   *
   * @throws \Exception
   *   Throws an exception when the file is not found.
   */
  protected function uploadRdfFile($filename, $files_path) {
    $id = $this->createFile($filename, $files_path);
    return RdfFileHandler::fileToUrl($this->files[$id]);
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

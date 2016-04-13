<?php

namespace Drupal\joinup\Context;


use Drupal\Component\Uuid\Php;
use Drupal\file\Entity\File;
use Drupal\rdf_entity\Entity\Query\Sparql\SparqlArg;

/**
 * Helper trait for behat tests.
 */
trait JoinupTrait {
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
  public function createFile($filename, $files_path) {
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

  /**
   * Returns the rdf entity with the given name and type.
   *
   * If multiple asset distributions have the same name,
   * the first one will be returned.
   *
   * @param string $title
   *   The rdf entity title.
   * @param string $type
   *   The rdf entity type.
   *
   * @return \Drupal\rdf_entity\Entity\Rdf
   *   The asset distribution.
   *
   * @throws \InvalidArgumentException
   *   Thrown when an asset distribution with the given name does not exist.
   */
  protected function getRdfEntityByLabel($title, $type) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', $type)
      ->condition('?entity', SparqlArg::uri('http://purl.org/dc/terms/title'), SparqlArg::literal($title))
      ->range(0, 1);
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("The rdf entity with the name '$title' was not found.");
    }

    return \Drupal::entityTypeManager()
      ->getStorage('rdf_entity')
      ->loadUnchanged(reset($result));
  }

  /**
   * Returns a random URI ID for the collection.
   *
   * @return string
   *   A string URI
   */
  private function getRandomUri() {
    $php = new Php();
    return 'http://example.com/' . $php->generate();
  }

}

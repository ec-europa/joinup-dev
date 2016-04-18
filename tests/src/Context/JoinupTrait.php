<?php

namespace Drupal\joinup\Context;


use Drupal\Component\Uuid\Php;
use Drupal\file\Entity\File;
use Drupal\rdf_entity\Entity\Rdf;

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
  protected static function getRdfEntityByLabel($title, $type = NULL) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('label', $title)
      ->range(0, 1);
    if ($type) {
      $result = $query->condition('rid', $type)->execute();
    }
    else {
      $result = $query->execute();
    }

    if (empty($result)) {
      throw new \InvalidArgumentException("The rdf entity with the name '$title' was not found.");
    }

    return Rdf::load(reset($result));
  }

  /**
   * Returns the rdf entity with the given name and type.
   *
   * If multiple asset distributions have the same name,
   * the first one will be returned.
   *
   * This method resets the static cache before loading the entity and
   * should be used when an entity is altered through e.g. a hook update.
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
  protected static function getRdfEntityByLabelUnchanged($title, $type) {
    $query = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', $type)
      ->condition('label', $title)
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
   * Returns a random URI ID for the collection.
   *
   * @return string
   *   A string URI
   */
  public function getRandomUri() {
    $php = new Php();
    return 'http://example.com/' . $php->generate();
  }

  /**
   * Checks the number of available rdf entities filtered by bundle.
   *
   * @param int $number
   *   The expected number of rdf entities.
   * @param string $type
   *   The rdf type.
   *
   * @throws \Exception
   *   Thrown when the number of rdf entities does not
   *   match the expectation.
   */
  public function assertRdfEntityCount($number, $type) {
    $actual = \Drupal::entityQuery('rdf_entity')
      ->condition('rid', $type)
      ->count()
      ->execute();
    if ($actual != $number) {
      throw new \Exception("Wrong number of rdf_entities. Expected number: $number, actual number: $actual.");
    }
  }

}

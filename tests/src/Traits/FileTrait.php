<?php

declare(strict_types = 1);

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
    $destination = \Drupal::service('file_system')->copy($path, $uri);
    $file = File::create(['uri' => $destination, 'filename' => $filename]);
    $file->save();

    $this->files[$file->id()] = $file;

    return $file;
  }

  /**
   * Handles file fields when creating entities in step definitions.
   *
   * This allows to use the filenames of the files in the fixtures folder (e.g.
   * "logo.png") in step definitions. It will import the file into Drupal, and
   * replace the filename with the ID of the file entity.
   *
   * @param array $values
   *   An associative array, keyed by field name, containing field values of the
   *   entity that is being saved. Any file fields in this array will have their
   *   values changed into File IDs by reference.
   * @param string $entity_type
   *   The entity type of the entity that is being saved.
   * @param string $bundle
   *   The bundle of the entity that is being saved.
   *
   * @throws \Exception
   *   Thrown when a field name is present in the $values array which does not
   *   exist on the specified entity bundle.
   */
  protected function handleFileFields(array &$values, $entity_type, $bundle) {
    $entity_field_manager = \Drupal::service('entity_field.manager');
    $fields = $entity_field_manager->getFieldDefinitions($entity_type, $bundle);

    foreach ($values as $field_name => $value) {
      if (!isset($fields[$field_name])) {
        throw new \Exception("Field $field_name is not set on entity $entity_type  : $bundle");
      }
      if (empty($value)) {
        continue;
      }
      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field_info */
      $field_info = $fields[$field_name];
      if (in_array($field_info->getType(), ['image', 'file'])) {
        $values[$field_name] = $this->createFile($value)->get('uri')->value;
      }
    }
  }

  /**
   * Remove any created files.
   *
   * @afterScenario
   */
  public function cleanFiles() {
    // Remove the image entities that were attached to the collections.
    foreach ($this->files as $file) {
      $file->delete();
    }
  }

}

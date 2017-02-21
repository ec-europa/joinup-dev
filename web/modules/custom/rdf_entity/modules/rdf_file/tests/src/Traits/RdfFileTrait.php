<?php

namespace Drupal\Tests\rdf_file\Traits;

use Drupal\file\Entity\File;
use Drupal\Tests\rdf_entity\Traits\RdfEntityCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Functions regarding the rdf file manipulation.
 */
trait RdfFileTrait {

  use RdfEntityCreationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   *    The created file.
   */
  public function getTestFile($type_name, $size = NULL) {
    // Get a file to upload.
    $file = current($this->drupalGetTestFiles($type_name, $size));
    // Add a filesize property to files as would be read by
    // \Drupal\file\Entity\File::load().
    $file->filesize = filesize($file->uri);
    return File::create((array) $file);
  }

}

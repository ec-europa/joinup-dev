<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\Traits;

use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;

/**
 * Functions regarding the file URL manipulation.
 */
trait FileUrlTrait {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Retrieves a sample file of the specified type.
   *
   * @return \Drupal\file\FileInterface
   *   The created file.
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

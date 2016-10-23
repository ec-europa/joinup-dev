<?php

namespace Drupal\joinup\Context;

use Behat\Mink\Driver\GoutteDriver;
use Drupal\DrupalExtension\Context\MinkContext as DrupalExtensionMinkContext;

/**
 * Provides step definitions for interacting with Mink.
 */
class MinkContext extends DrupalExtensionMinkContext {

  /**
   * Attach File to field.
   *
   * @param string $field
   *   Field name.
   * @param string $path
   *   File path.
   */
  public function attachFileToField($field, $path) {
    $field = $this->fixStepArgument($field);
    if ($this->getMinkParameter('files_path')) {
      $full_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR)
      . DIRECTORY_SEPARATOR
        . $path;
      if (is_file($full_path)) {
        $path = $full_path;
      }
    }
    if (!$this->getSession()->getDriver() instanceof GoutteDriver) {
      $temp_zip = tempnam('', 'WebDriverZip');
      $zip = new \ZipArchive();
      $zip->open($temp_zip, \ZipArchive::CREATE);
      $zip->addFile($path, basename($path));
      $zip->close();
      $path = $this->getSession()->getDriver()->getWebDriverSession()->file([
        'file' => base64_encode(file_get_contents($temp_zip)),
      ]);
    }
    $this->getSession()->getPage()->attachFileToField($field, $path);
    if (isset($temp_zip)) {
      unlink($temp_zip);
    }
  }

}

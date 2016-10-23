<?php

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\MinkContext as DrupalExtensionMinkContext;

/**
 * Provides step definitions for interacting with Mink.
 */
class MinkContext extends DrupalExtensionMinkContext {
  
  public function attachFileToField($field, $path) {
      $field = $this->fixStepArgument($field);

      if ($this->getMinkParameter('files_path')) {
          $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
          if (is_file($fullPath)) {
              $path = $fullPath;
          }
      }

      if (!$this->getSession()->getDriver() instanceof \Behat\Mink\Driver\GoutteDriver) {
          $tempZip = tempnam('', 'WebDriverZip');
          $zip = new \ZipArchive();
          $zip->open($tempZip, \ZipArchive::CREATE);
          $zip->addFile($path, basename($path));
          $zip->close();
          $path = $this->getSession()->getDriver()->getWebDriverSession()->file([
              'file' => base64_encode(file_get_contents($tempZip))
          ]);
      }

      $this->getSession()->getPage()->attachFileToField($field, $path);

      if (isset($tempZip)) {
          unlink($tempZip);
      }
  }
}

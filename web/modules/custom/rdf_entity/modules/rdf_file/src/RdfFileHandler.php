<?php

namespace Drupal\rdf_file;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\rdf_file\Entity\RemoteFile;

/**
 * Helper class for turning files into public URLs and back.
 */
class RdfFileHandler {

  /**
   * Get public dereferenceable URL from file.
   *
   * @param \Drupal\file\FileInterface $file
   *   File.
   *
   * @return string
   *   URL.
   */
  static public function fileToUrl(FileInterface $file) {
    if ($file instanceof RemoteFile) {
      throw new \Exception('Only regular files can be converted.');
    }
    return "http://localhost/file-dereference/" . $file->id();
  }

  /**
   * Get the right file object from an URL.
   *
   * @param string $url
   *   URL.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The file object.
   */
  public function urlToFile($url) {
    $matches = [];
    preg_match('/\/file-dereference\/([0-9]+)/', $url, $matches);
    if (!empty($matches)) {
      return File::load($matches[1]);
    }
    return RemoteFile::load($url);
  }

}

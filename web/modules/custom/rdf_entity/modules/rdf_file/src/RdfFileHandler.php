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
    global $base_url;
    if ($file instanceof RemoteFile) {
      throw new \Exception('Only regular files can be converted.');
    }
    // @see rdf_file.routing.yml for dereference redirect.
    return $base_url . '/file-dereference/' . $file->id();
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
    // Not a url, but a normal file id.
    // This can occurs when an object is created in code (not through a form).
    if (is_numeric($url)) {
      return File::load($url);
    }
    $matches = [];
    preg_match('/\/file-dereference\/([0-9]+)/', $url, $matches);
    if (!empty($matches)) {
      return File::load($matches[1]);
    }
    return RemoteFile::load($url);
  }

}

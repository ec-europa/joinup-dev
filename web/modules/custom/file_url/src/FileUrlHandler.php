<?php

namespace Drupal\file_url;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file_url\Entity\RemoteFile;

/**
 * Helper class for turning files into public URLs and back.
 */
class FileUrlHandler {

  /**
   * Get public dereferenceable URL from file.
   *
   * @param \Drupal\file\FileInterface $file
   *   File.
   *
   * @return string
   *   URL.
   *
   * @throws \Exception
   *   When a RemoteFile has been accidentally passed.
   */
  public static function fileToUrl(FileInterface $file) {
    global $base_url;
    if ($file instanceof RemoteFile) {
      throw new \Exception('Only regular files can be converted.');
    }
    // @see file_url.routing.yml for dereference redirect.
    return $base_url . '/file-dereference/' . $file->id();
  }

  /**
   * Get the right file object from an URL.
   *
   * @param string $url
   *   URL.
   *
   * @return \Drupal\file\FileInterface
   *   The file object.
   */
  public static function urlToFile($url) {
    // Not a url, but a normal file ID. This can occurs when an object is
    // created in code (not through a form).
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

  /**
   * Checks if a file entity is s remote file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being checked.
   *
   * @return bool
   *   TRUE if it's a remote file.
   */
  public static function isRemote(FileInterface $file) {
    return $file instanceof RemoteFile;
  }

}

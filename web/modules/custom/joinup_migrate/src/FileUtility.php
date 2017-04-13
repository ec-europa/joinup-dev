<?php

namespace Drupal\joinup_migrate;

use GuzzleHttp\Client;

/**
 * Provides utility methods for files import.
 */
class FileUtility {

  /**
   * Checks that the  legacy site webroot is available.
   *
   * @param string $webroot
   *   The legacy site webroot.
   *
   * @throws \Exception
   *   When the legacy site webroot or doesn't exist or is not readable.
   */
  public static function checkLegacySiteWebRoot($webroot) {
    if (empty($webroot)) {
      throw new \Exception('The web root of the D6 site is not configured. Please run `phing setup-migration`.');
    }

    $files = "$webroot/sites/default/files";
    $valid = is_dir($files) && is_readable($files);
    if (!$valid && !is_dir($files)) {
      // It might be a remote location, accessible via HTTP. We call directly
      // the Guzzle client class as the container might not be in place yet.
      $response = (new Client())->request('HEAD', "$files/.htaccess", ['http_errors' => FALSE]);
      if ($response->getStatusCode() === 403) {
        $valid = TRUE;
      }
    }

    if (!$valid) {
      throw new \Exception("The web root of the D6 site '$files' doesn't exist or is not readable.");
    }
  }

}

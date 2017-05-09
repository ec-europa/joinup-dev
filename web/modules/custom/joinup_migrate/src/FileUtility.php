<?php

namespace Drupal\joinup_migrate;

use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\HandlerStack;

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
   *   When the legacy site webroot is not defined or doesn't exist or is not
   *   readable.
   */
  public static function checkLegacySiteWebRoot($webroot) {
    if (empty($webroot)) {
      throw new \Exception('The web root of the D6 site is not configured. Please run `phing setup-migration`.');
    }

    $files = "$webroot/sites/default/files";
    $valid = is_dir($files) && is_readable($files);
    if (!$valid && !is_dir($files)) {
      // It might be a remote location, accessible via HTTP.
      $options = ['http_errors' => FALSE];
      // We call directly the Guzzle client class because the container might
      // not be in place yet.
      $http_client_factory = new ClientFactory(HandlerStack::create());
      $http_client = $http_client_factory->fromOptions();
      // Do three tries to avoid false positives.
      for ($i = 0; $i < 3; $i++) {
        // logo_en.gif is well-known file used in the site.
        $response = $http_client->request('HEAD', "$files/logo_en.gif", $options);
        if ($response->getStatusCode() === 200) {
          $valid = TRUE;
          break;
        }
      }
    }

    if (!$valid) {
      throw new \Exception("The web root of the D6 site '$files' doesn't exist or is not readable.");
    }
  }

}

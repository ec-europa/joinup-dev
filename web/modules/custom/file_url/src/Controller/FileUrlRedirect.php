<?php

namespace Drupal\file_url\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirects to the file location.
 *
 * This makes sure that, when URL is used externally, the files can be
 * dereferenced.
 */
class FileUrlRedirect extends ControllerBase {

  /**
   * Redirect to the actual file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect object.
   */
  public function redirectToFile(FileInterface $file) {
    $url = $file->toUrl()->toString();
    return new RedirectResponse($url);
  }

}

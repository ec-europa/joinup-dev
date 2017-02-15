<?php

namespace Drupal\rdf_file\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirects to the file location.
 *
 * This makes sure that when the SPARQL endpoint is used externally;
 * that the files can be dereferenced.
 */
class RdfFileRedirect extends ControllerBase {

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
    $url = $file->toUrl();
    return new RedirectResponse($url);
  }

}

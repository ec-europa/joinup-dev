<?php

namespace Drupal\joinup_core\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides a route callback to redirect /data/{uuid} to the RDF canonical page.
 */
class IdRedirect extends ControllerBase {

  /**
   * Redirects to the RDF entity canonical page.
   *
   * @param string $uuid
   *   The unique part of the RDF entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function redirectToRdfEntity($uuid) {
    return $this->redirect('entity.rdf_entity.canonical', [
      'rdf_entity' => "http://data.europa.eu/w21/$uuid",
    ], [], 301);
  }

}

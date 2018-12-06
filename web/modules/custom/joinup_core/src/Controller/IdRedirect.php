<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a route callback to redirect /data/{uuid} to the RDF canonical page.
 */
class IdRedirect extends ControllerBase {

  /**
   * Redirects to the RDF entity canonical page.
   *
   * @param string $namespace
   *   The persistent URI namespace. See http://data.europa.eu/URI.html.
   * @param string $uuid
   *   The unique part of the RDF entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   */
  public function redirectToRdfEntity(string $namespace, string $uuid): RedirectResponse {
    if (!$entity_type_id = static::getEntityTypeFromPersistentUriNamespace($namespace)) {
      return $this->redirect('joinup_core.not_found', [], [], 404);
    }

    return $this->redirect("entity.$entity_type_id.canonical", [
      $entity_type_id => "http://data.europa.eu/$namespace/$uuid",
    ], [], 301);
  }

  /**
   * Returns the entity type given an EU persistent URI namespace.
   *
   * @param string|null $namespace
   *   (optional) The EU persistent URI namespace.
   *
   * @return string[]|string|null
   *   If $namespace is missed, it returns the whole mapping array. If
   *   $namespace is passe, returns the mapped entity type or NULL if none.
   *
   * @see http://data.europa.eu/URI.html
   */
  public static function getEntityTypeFromPersistentUriNamespace(?string $namespace = NULL) {
    $namespaces = [
      'w21' => 'rdf_entity',
      'dr8' => 'taxonomy_term',
    ];

    if (!$namespace) {
      return $namespaces;
    }

    return $namespaces[$namespace] ?? NULL;
  }

}

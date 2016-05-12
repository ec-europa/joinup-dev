<?php
/**
 * @file
 * Contains \Drupal\rdf_taxonomy\Routing\RouteSubscriber.
 */

namespace Drupal\rdf_taxonomy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Loosen the constraint that taxonomy terms should have numeric ids.
    $taxonomy_routes = [
      'entity.taxonomy_term.edit_form',
      'entity.taxonomy_term.delete_form',
      'entity.taxonomy_term.canonical'
    ];
    foreach ($taxonomy_routes as $taxonomy_route) {
      if ($route = $collection->get($taxonomy_route)) {
        $route->setRequirement('taxonomy_term', '.+');
      }
    }
  }
}
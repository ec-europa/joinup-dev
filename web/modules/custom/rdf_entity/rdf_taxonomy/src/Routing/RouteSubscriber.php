<?php

namespace Drupal\rdf_taxonomy\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\taxonomy\Controller\TaxonomyController;
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
      'entity.taxonomy_term.canonical',
    ];
    foreach ($taxonomy_routes as $taxonomy_route) {
      if ($route = $collection->get($taxonomy_route)) {
        $route->setRequirement('taxonomy_term', '.+');
      }
    }

    // Use our own list builder as with taxonomy terms stored in triple store we
    // don't have weights, as consequence the core term list builder will fail.
    $route = $collection->get('entity.taxonomy_vocabulary.overview_form');
    if ($route) {
      $route->setDefaults([
        '_title_callback' => TaxonomyController::class . '::vocabularyTitle',
        '_entity_list' => 'taxonomy_term',
      ]);
      $route->setOption('parameters', [
        'taxonomy_vocabulary' => [
          'type' => 'entity:taxonomy_vocabulary',
        ],
      ]);
    }
  }

}

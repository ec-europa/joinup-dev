<?php

namespace Drupal\joinup_licence\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters rdf_entity canonical route requirements.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Used to deny access to spdx licence canonical route.
    if ($route = $collection->get('entity.rdf_entity.canonical')) {
      $route->addRequirements(['_canonical_route_restrict' => 'TRUE']);
    }
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\eif\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters existing routes for EIF specific use cases.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.eif_recommendations.page')) {
      $route->addRequirements(['_custom_access' => 'eif.helper:access']);
    }
  }

}

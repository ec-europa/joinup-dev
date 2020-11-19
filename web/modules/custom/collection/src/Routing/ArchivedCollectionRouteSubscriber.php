<?php

declare(strict_types = 1);

namespace Drupal\collection\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the 'joinup_group.add_content' route.
 */
class ArchivedCollectionRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('joinup_group.add_content')) {
      $route->setRequirement('_archived_collection', 'TRUE');
    }
  }

}

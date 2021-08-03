<?php

declare(strict_types = 1);

namespace Drupal\collection\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the 'joinup_group.add_content' route.
 */
class ArchivedCommunityRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $community) {
    if ($route = $community->get('joinup_group.add_content')) {
      $route->setRequirement('_archived_collection', 'TRUE');
    }
  }

}

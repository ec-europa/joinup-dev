<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\joinup_community_content\Controller\CommunityContentController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the 'joinup_group.add_content' route.
 */
class CreateCommunityContentRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('joinup_group.add_content')) {
      $route->setRequirement('_custom_access', CommunityContentController::class . '::createAccess');
    }
  }

}

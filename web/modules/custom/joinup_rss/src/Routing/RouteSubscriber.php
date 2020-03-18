<?php

declare(strict_types = 1);

namespace Drupal\joinup_rss\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\joinup_rss\Controller\GroupFeedController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Add extra access checks for routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.group_feed.rss_feed')) {
      $route->setRequirement('_custom_access', GroupFeedController::class . '::access');
    }
  }

}

<?php

namespace Drupal\joinup_collection\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\joinup_collection\Controller\JoinupCollectionLeaveController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('collection.leave_confirm_form')) {
      $route->setRequirement('_custom_access', JoinupCollectionLeaveController::class . '::access');
    }
  }

}

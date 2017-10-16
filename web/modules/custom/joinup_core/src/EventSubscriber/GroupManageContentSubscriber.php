<?php

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the 'view.group_content_management.manage' route.
 */
class GroupManageContentSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('view.group_content_management.manage')) {
      $route->setOption('_admin_route', TRUE);
    }
  }

}

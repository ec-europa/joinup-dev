<?php

namespace Drupal\joinup_front_page\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $routes = $collection->all();
    foreach ($routes as $route_name => $route) {
      switch ($route_name) {
        case 'entity.menu.add_link_form':
          $route->setRequirements(['_custom_access' => '\Drupal\joinup_front_page\Access\MenuAccess::menuLinkAddAccess']);
          break;

        case 'menu_ui.link_edit':
          $route->setRequirements(['_custom_access' => '\Drupal\joinup_front_page\Access\MenuAccess::menuLinkItemEditAccess']);
          break;

        case 'entity.menu_link_content.canonical':
          $route->setRequirements(['_custom_access' => '\Drupal\joinup_front_page\Access\MenuAccess::menuLinkPluginEditAccess']);
          break;

      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after menu_admin_per_menu, which has priority -220.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -221];
    return $events;
  }

}

<?php

namespace Drupal\custom_page\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters existing routes related to custom pages.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.ogmenu_instance.edit_form')) {
      $route->addDefaults([
        '_title_callback' => '\Drupal\custom_page\Controller\CustomPageController::editFormTitle',
      ]);
    }
  }

}

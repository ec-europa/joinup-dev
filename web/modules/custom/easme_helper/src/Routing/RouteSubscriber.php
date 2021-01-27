<?php
namespace Drupal\easme_helper\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber
 * @package Drupal\easme_helper
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @param RouteCollection $collection
   */
  public function alterRoutes(RouteCollection $collection) {

    // Reroute contact page.
    if ($route = $collection->get('contact_form.contact_page')) {
      $route->setDefault('_controller', '\Drupal\easme_helper\Controller\GeneralController::contactPage');
    }

  }

}

<?php

declare(strict_types = 1);

namespace Drupal\easme_helper\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * The RouteSubscriber class.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Reroute contact page.
    if ($route = $collection->get('contact_form.contact_page')) {
      $route->setDefault('_controller', '\Drupal\easme_helper\Controller\GeneralController::contactPage');
    }
  }

}

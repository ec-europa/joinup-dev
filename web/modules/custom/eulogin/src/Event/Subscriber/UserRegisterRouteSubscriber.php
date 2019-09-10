<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\joinup_eulogin\Controller\UserRegisterRedirectController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the 'user.register' route.
 */
class UserRegisterRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.register')) {
      $route
        ->setDefaults(['_controller' => UserRegisterRedirectController::class . '::redirectUserRegister'])
        ->setRequirements(['_user_is_logged_in' => 'FALSE']);
    }
  }

}

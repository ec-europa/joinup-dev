<?php

namespace Drupal\joinup_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters existing routes for the Joinup user multiple cancel form.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Override the confirmation form to delete multiple users with our version
    // that prevents deletion of users that are sole owners of collections.
    if ($route = $collection->get('user.multiple_cancel_confirm')) {
      $route->addDefaults([
        '_form' => '\Drupal\joinup_group\Form\UserMultipleCancelConfirm',
      ]);
    }
  }

}

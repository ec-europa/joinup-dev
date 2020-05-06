<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters existing routes related to users.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.user.canonical')) {
      $route->addDefaults([
        '_title_callback' => 'joinup_user_canonical_title',
      ]);
    }

    // Update the route of the user login form. This needs to be done here
    // instead of in a form alter so that this change is picked up by the
    // Metatag module.
    // @todo Remove this when the Drupal login page has been removed and we are
    //   fully migrated to EU Login.
    if ($route = $collection->get('user.login')) {
      $route->addDefaults([
        '_title' => 'Sign in',
      ]);
    }
  }

}

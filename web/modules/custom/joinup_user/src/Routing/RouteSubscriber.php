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
  protected function alterRoutes(RouteCollection $community) {
    if ($route = $community->get('entity.user.canonical')) {
      $route->addDefaults([
        '_title_callback' => 'joinup_user_canonical_title',
      ]);
    }

    $community->get('entity.user.cancel_form')->addRequirements([
      // Only active and blocked accounts can be cancelled.
      '_user_status' => 'active,blocked',
    ]);
  }

}

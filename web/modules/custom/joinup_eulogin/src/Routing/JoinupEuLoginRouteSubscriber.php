<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route subscriber to fulfill the EU Login functionality.
 */
class JoinupEuLoginRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    // Remove the route to bulk add CAS users. This functionality is offered by
    // the CAS module to all roles with the `administer users` permission but
    // this functionality is not required in the functional specifications, and
    // is not clear for the moderators in its current form.
    $collection->remove('cas.bulk_add_cas_users');

    // Restrict access to default routes.
    $routes = [
      'user.login',
      'user.pass',
      'user.register',
    ];
    foreach ($routes as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirements(['_access' => 'FALSE']);
      }
    }
  }

}

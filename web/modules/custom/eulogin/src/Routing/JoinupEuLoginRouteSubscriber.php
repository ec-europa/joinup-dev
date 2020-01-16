<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\joinup_eulogin\Controller\UserRegisterRedirectController;
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

    // User registration should redirect to EU Login register.
    if ($route = $collection->get('user.register')) {
      $route
        ->setDefaults(['_controller' => UserRegisterRedirectController::class . '::redirectUserRegister'])
        ->setRequirements(['_user_is_logged_in' => 'FALSE']);
    }

    // Always deny access to the user login forms. Users are expected to log in
    // through EU Login. The actual login form is excluded from this; for the
    // time being we are showing a warning to people who might have missed the
    // news about the move to EU Login.
    // @see joinup_eulogin_form_user_login_form_alter()
    foreach (['user.register', 'user.pass'] as $route_name) {
      if ($route = $collection->get($route_name)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

}

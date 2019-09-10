<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin\Event\Subscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a route subscriber to fulfill the EU Login functionality.
 */
class JoinupEuLoginRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Remove the route to bulk add CAS users. This functionality is offered by
    // the CAS module to all roles with the `administer users` permission but
    // this functionality is not required in the functional specifications, and
    // is not clear for the moderators in its current form.
    $collection->remove('cas.bulk_add_cas_users');
  }

}

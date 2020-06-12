<?php

declare(strict_types = 1);

namespace Drupal\joinup_user\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\joinup_user\Entity\JoinupUserInterface;
use Symfony\Component\Routing\Route;

/**
 * Access checker the allows/denies the access based on the user status.
 */
class UserStatusAccessCheck implements AccessInterface {

  /**
   * Checks the access based on the '_user_status' route requirement.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Exception
   *   Thrown if the route requirement:
   *   - Has an invalid value.
   *   - Has been set in o route that doesn't support this requirement.
   */
  public function access(Route $route, RouteMatchInterface $route_match): AccessResultInterface {
    $allowed_statuses = array_map('trim', explode(',', $route->getRequirement('_user_status')));
    $valid_statuses = ['active', 'blocked', 'cancelled'];
    if ($invalid_statuses = array_diff($allowed_statuses, $valid_statuses)) {
      throw new \Exception("Invalid route requirement values ('" . implode("', '", $invalid_statuses) . "') for '_user_status'. The requirement should be a string that concatenates one of the following values, separated by comma: 'active', 'blocked', 'cancelled'.");
    }

    if (!($account = $route_match->getParameter('user')) || !$account instanceof JoinupUserInterface) {
      throw new \Exception("The '_user_status' requirement is used on an invalid route.");
    }

    foreach ($allowed_statuses as $key) {
      // Can be: isActive, isBlocked, isCancelled.
      $method = 'is' . ucfirst($key);
      if ($account->{$method}()) {
        return AccessResult::allowed()->addCacheableDependency($account);
      }
    }

    // Let other parties decide.
    return AccessResult::neutral();
  }

}

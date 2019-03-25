<?php

declare(strict_types = 1);

namespace Drupal\joinup\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Restricts canonical routes to UID 1 only.
 */
class CanonicalRouteRestrict implements AccessInterface {

  /**
   * Constructs a CanonicalRouteRestrict object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    $rdf_entity = $route_match->getParameter('rdf_entity');
    if (!($rdf_entity instanceof RdfInterface) || $rdf_entity->bundle() !== 'spdx_licence') {
      return AccessResult::neutral()->addCacheContexts(['user']);
    }

    if ($account->id() === 1) {
      return AccessResult::allowed()->addCacheContexts(['user']);
    }
    return AccessResult::forbidden()->addCacheContexts(['user']);
  }

}

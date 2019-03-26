<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Restricts entity canonical routes to UID 1 only.
 */
class EntityCanonicalRouteRestrict implements AccessInterface {

  /**
   * Constructs a EntityCanonicalRouteRestrict object.
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
      // In case the route the canonical route of the spdx_licence, return a
      // positive access result as a neutral would deny the access to the route.
      // @see \Drupal\Core\Access\AccessResult::andIf().
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}

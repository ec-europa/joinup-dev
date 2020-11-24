<?php

declare(strict_types = 1);

namespace Drupal\collection\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\collection\Entity\CollectionInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides an access checker for the '_archived_collection' requirement.
 */
class ArchivedCollectionAccessCheck implements AccessInterface {

  /**
   * Checks access for routes having the '_archived_collection' requirement.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parameterized route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Exception
   *   If the '_archived_collection' has an invalid value.
   */
  public function access(Route $route, RouteMatchInterface $route_match): AccessResultInterface {
    $requirement = $route->getRequirement('_archived_collection');
    if ($requirement !== 'TRUE') {
      throw new \Exception("Invalid value '{$requirement}' for route '_archived_collection' requirement.");
    }

    $collection = $route_match->getParameter('rdf_entity');

    // If the collection is archived, content creation is not allowed.
    if ($collection instanceof CollectionInterface && $collection->get('field_ar_state')->value === 'archived') {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}

<?php

namespace Drupal\joinup_core\Plugin\OgGroupResolver;

use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;

/**
 * Resolves the group from the route.
 *
 * Use this to make the group (collection or solution) available as a route
 * context on paths that are not defined as entity link templates.
 *
 * @OgGroupResolver(
 *   id = "joinup_route_group",
 *   label = "Group entity from current route",
 *   description = @Translation("Checks if the current route is an entity path that belongs to a group entity.")
 * )
 */
class JoinupRouteGroupResolver extends RouteGroupResolver {

  /**
   * {@inheritdoc}
   */
  protected function getContentEntityPaths() {
    return [
      '/rdf_entity/{rdf_entity}/moderate' => 'rdf_entity',
    ];
  }

}

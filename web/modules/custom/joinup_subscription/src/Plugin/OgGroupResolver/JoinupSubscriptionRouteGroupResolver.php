<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\Plugin\OgGroupResolver;

use Drupal\og\Plugin\OgGroupResolver\RouteGroupResolver;

/**
 * Resolves the group for subscription routes.
 *
 * This ensures we can show the group header block on group subscriber reports.
 *
 * @OgGroupResolver(
 *   id = "joinup_subscription_routes",
 *   label = "Routes related to subscriptions",
 * )
 */
class JoinupSubscriptionRouteGroupResolver extends RouteGroupResolver {

  /**
   * {@inheritdoc}
   */
  protected function getContentEntityPaths() {
    return [
      '/rdf_entity/{rdf_entity}/reports/subscribers' => 'rdf_entity',
    ];
  }

}

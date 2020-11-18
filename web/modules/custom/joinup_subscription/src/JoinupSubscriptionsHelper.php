<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\joinup_community_content\CommunityContentHelper;

/**
 * Helper class for Joinup subscriptions.
 */
class JoinupSubscriptionsHelper {

  /**
   * An array of bundles that can be subscribed to, keyed by entity type.
   */
  const COLLECTION_BUNDLES = [
    'rdf_entity' => ['solution'],
    'node' => CommunityContentHelper::BUNDLES,
  ];

  /**
   * An array of bundles that can be subscribed to, keyed by entity type.
   */
  const SOLUTION_BUNDLES = [
    'node' => CommunityContentHelper::BUNDLES,
  ];

  /**
   * Returns the default value for the collection membership subscriptions.
   *
   * @return array
   *   A list of entries, each of which contains an entity type and a bundle
   *   value.
   */
  public static function getCollectionBundlesDefaultValue(): array {
    $bundles_value = [];
    foreach (JoinupSubscriptionsHelper::COLLECTION_BUNDLES as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $bundles_value[] = ['entity_type' => $entity_type, 'bundle' => $bundle];
      }
    }
    return $bundles_value;
  }

  /**
   * Returns the default value for the solution membership subscriptions.
   *
   * @return array
   *   A list of entries, each of which contains an entity type and a bundle
   *   value.
   */
  public static function getSolutionBundlesDefaultValue(): array {
    return array_map(function (string $bundle): array {
      return ['entity_type' => 'node', 'bundle' => $bundle];
    }, JoinupSubscriptionsHelper::SOLUTION_BUNDLES['node']);
  }

}

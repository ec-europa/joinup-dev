<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\joinup_community_content\CommunityContentHelper;

/**
 * Helper class for Joinup subscriptions.
 */
class JoinupSubscriptionsHelper {

  /**
   * An array of group content bundles that can be subscribed to.
   *
   * Users subscribing to collections or solutions can opt to receive updates
   * about all community content, and collection subscribers can also get
   * information about new solutions that are published in the collection.
   */
  const SUBSCRIPTION_BUNDLES = [
    'collection' => [
      'rdf_entity' => ['solution'],
      'node' => CommunityContentHelper::BUNDLES,
    ],
    'solution' => [
      'node' => CommunityContentHelper::BUNDLES,
    ],
  ];

  /**
   * Returns the default value for the collection membership subscriptions.
   *
   * @return array
   *   A list of entries, each of which contains an entity type and a bundle
   *   value.
   */
  public static function getSubscriptionBundlesDefaultValue(string $subscription_bundle): array {
    $bundles_value = [];
    foreach (JoinupSubscriptionsHelper::SUBSCRIPTION_BUNDLES[$subscription_bundle] as $entity_type => $bundles) {
      foreach ($bundles as $bundle) {
        $bundles_value[] = ['entity_type' => $entity_type, 'bundle' => $bundle];
      }
    }
    return $bundles_value;
  }

}

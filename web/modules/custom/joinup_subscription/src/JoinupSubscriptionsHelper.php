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
   * Users subscribing to communities or solutions can opt to receive updates
   * about all community content, and collection subscribers can also get
   * information about new solutions that are published in the collection.
   */
  const SUBSCRIPTION_BUNDLES = [
    'collection' => [
      'rdf_entity' => ['solution'],
      'node' => CommunityContentHelper::BUNDLES,
    ],
    'solution' => [
      'rdf_entity' => ['asset_distribution', 'asset_release'],
      'node' => CommunityContentHelper::BUNDLES,
    ],
  ];

}

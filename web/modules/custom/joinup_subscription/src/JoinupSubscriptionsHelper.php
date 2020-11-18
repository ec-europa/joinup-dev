<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\joinup_community_content\CommunityContentHelper;

/**
 * Interface for Joinup subscriptions.
 */
interface JoinupSubscriptions {

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

}

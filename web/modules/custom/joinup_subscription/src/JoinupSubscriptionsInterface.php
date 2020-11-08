<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription;

use Drupal\joinup_community_content\CommunityContentHelper;

/**
 * Interface for Joinup subscriptions.
 */
interface JoinupSubscriptionsInterface {

  /**
   * An array of bundles that can be subscribed to, keyed by entity type.
   */
  const BUNDLES = [
    'node' => CommunityContentHelper::BUNDLES,
  ];

}

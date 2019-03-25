<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content;

/**
 * Contains helper methods for dealing with community content.
 */
class CommunityContentHelper {

  /**
   * Returns an array of node bundles that are considered community content.
   *
   * @return array
   *   An array of node bundle IDs.
   */
  public static function getBundles(): array {
    return ['discussion', 'document', 'event', 'news'];
  }

  /**
   * Returns whether the passed in bundle is a community content bundle.
   *
   * @param string $bundle
   *   The bundle name to check.
   *
   * @return bool
   *   TRUE if the passed in bundle is a community content bundle.
   */
  public static function isCommunityContentBundle(string $bundle): bool {
    return \in_array($bundle, self::getBundles(), TRUE);
  }

  /**
   * Returns the workflow states that require attention from a moderator.
   *
   * @param string $bundle
   *   Optional ID of the community content bundle for which to return workflow
   *   states. If omitted, workflow states for all bundles will be returned.
   *
   * @return string[]
   *   An array of workflow state IDs.
   */
  public static function getModeratorAttentionNeededStates($bundle = NULL): array {
    $states = [
      'discussion' => [
        'proposed',
      ],
      'document' => [
        'deletion_request',
        'proposed',
      ],
      'event' => [
        'deletion_request',
        'proposed',
      ],
      'news' => [
        'deletion_request',
        'proposed',
      ],
    ];

    if ($bundle) {
      return $states[$bundle];
    }

    // If the $bundle parameter is omitted, return the merged states from all
    // bundles.
    return array_reduce($states, function ($all_states, $bundle_states) {
      return array_unique(array_merge($all_states, $bundle_states));
    }, []);
  }

}

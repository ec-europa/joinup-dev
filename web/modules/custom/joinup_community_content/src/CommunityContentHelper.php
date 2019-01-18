<?php

namespace Drupal\joinup_community_content;

/**
 * Contains helper methods for dealing with community content.
 */
class CommunityContentHelper {

  /**
   * An array of node bundles that are considered community content.
   */
  const BUNDLES = ['discussion', 'document', 'event', 'news'];

  /**
   * Returns an array of node bundles that are considered community content.
   *
   * @return array
   *   An array of node bundle IDs.
   *
   * @deprecated
   *   Use static::BUNDLES instead.
   */
  public static function getBundles() {
    @trigger_error(__METHOD__ . ' is deprecated. Use CommunityContentHelper::BUNDLES instead.', E_USER_DEPRECATED);
    return static::BUNDLES;
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
  public static function getModeratorAttentionNeededStates($bundle = NULL) {
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

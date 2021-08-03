<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content;

use Drupal\Component\Render\MarkupInterface;

/**
 * Contains helper methods for dealing with community content.
 */
class CommunityContentHelper {

  /**
   * An array of node bundles that are considered community content.
   */
  const BUNDLES = ['discussion', 'document', 'event', 'news'];

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

  /**
   * Returns a generic description of community content.
   *
   * This description is used in various places, in the user interface.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   A translated markup.
   */
  public static function getCommunityContentDescription(): MarkupInterface {
    return t('KEEP UP TO DATE items, like news, events, discussions and documents can be included in both Communities and Solutions.');
  }

}

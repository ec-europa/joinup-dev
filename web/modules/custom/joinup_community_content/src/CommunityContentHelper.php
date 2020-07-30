<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Contains helper methods for dealing with community content.
 */
class CommunityContentHelper {

  /**
   * An array of node bundles that are considered community content.
   *
   * @deprecated in 1.62 and is removed from 2.0. Implementing code should not
   *   rely on a list of community content bundle IDs since this is an
   *   implementation detail. Use `$entity instanceof CommunityContentInterface`
   *   instead.
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6106
   */
  const BUNDLES = ['discussion', 'document', 'event', 'news'];

  /**
   * Returns whether the entity is a community content node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is a community content node, false otherwise.
   *
   * @deprecated in 1.62 and is removed from 2.0. Use `$entity instanceof
   *   CommunityContentInterface` instead.
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6106
   */
  public static function isCommunityContent(EntityInterface $entity): bool {
    return $entity instanceof NodeInterface && \in_array($entity->bundle(), self::BUNDLES);
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
   *
   * @deprecated in 1.62 and is removed from 2.0. This is functionality that is
   *   specific to a certain user interaction and is not generally reusable. It
   *   should be moved inside the scope of the calling code instead.
   * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6106
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

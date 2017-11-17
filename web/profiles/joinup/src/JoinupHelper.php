<?php

namespace Drupal\joinup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Helper class for Joinup.
 */
class JoinupHelper {

  /**
   * Returns whether the entity is an rdf solution.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is an rdf of bundle solution, false otherwise.
   */
  public static function isSolution(ContentEntityInterface $entity) {
    return $entity instanceof RdfInterface && $entity->bundle() === 'solution';
  }

  /**
   * Returns whether the entity is a community content node.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is a community content node, false otherwise.
   */
  public static function isCommunityContent(ContentEntityInterface $entity) {
    return $entity instanceof NodeInterface && in_array($entity->bundle(), CommunityContentHelper::getBundles());
  }

}

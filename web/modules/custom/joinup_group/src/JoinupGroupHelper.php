<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\comment\CommentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Exception\MissingGroupException;

/**
 * Static helper methods for dealing with groups in Joinup.
 */
class JoinupGroupHelper {

  /**
   * Group bundles.
   */
  const GROUP_BUNDLES = [
    'collection' => 'collection',
    'solution' => 'solution',
  ];

  /**
   * Returns the group the entity belongs to.
   *
   * This relies on the fact that in Joinup every group content entity only
   * belongs to a single group.
   *
   * Call this only if you transparently need to support both comment entities
   * and standard group content. If you are dealing only with group content,
   * then call `GroupContentInterface::getGroup()` instead.
   *
   * @todo Find the cases where this is called by comment entities and refactor
   *   them, so this can be removed.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to return the group. Comment entities are also
   *   supported.
   *
   * @return \Drupal\joinup_group\Entity\GroupInterface|null
   *   The group entity, or NULL if the entity doesn't have a group.
   */
  public static function getGroup(EntityInterface $entity): ?GroupInterface {
    if ($entity instanceof CommentInterface) {
      $entity = $entity->getCommentedEntity();
    }

    if ($entity instanceof GroupContentInterface) {
      try {
        return $entity->getGroup();
      }
      catch (MissingGroupException $e) {
      }
    }
    return NULL;
  }

}

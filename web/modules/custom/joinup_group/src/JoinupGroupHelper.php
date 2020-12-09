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
   * Content creation field machine names per group bundle.
   */
  const GROUP_CONTENT_CREATION = [
    'collection' => 'field_ar_content_creation',
    'solution' => 'field_is_content_creation',
  ];

  /**
   * Content moderation field machine names per group bundle.
   */
  const GROUP_MODERATION_FIELDS = [
    'collection' => 'field_ar_moderation',
    'solution' => 'field_is_moderation',
  ];

  /**
   * Workflow state field machine names per group bundle.
   */
  const GROUP_STATE_FIELDS = [
    'collection' => 'field_ar_state',
    'solution' => 'field_is_state',
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

  /**
   * Returns the content moderation status for the given group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group for which to return the content moderation value.
   *
   * @return int
   *   The content moderation status. Can be one of the following values:
   *   - CommunityContentWorkflowAccessControlHandler::PRE_MODERATION
   *   - CommunityContentWorkflowAccessControlHandler::POST_MODERATION
   */
  public static function getModeration(EntityInterface $entity): int {
    return (int) $entity->{self::GROUP_MODERATION_FIELDS[$entity->bundle()]}->first()->value;
  }

  /**
   * Returns the content creation option for the given group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group for which to return the content creation option value.
   *
   * @return string
   *   The content creation option value. Can be one of the following:
   *   - \Drupal\joinup_group\ContentCreationOptions::FACILITATORS
   *   - \Drupal\joinup_group\ContentCreationOptions::MEMBERS
   *   - \Drupal\joinup_group\ContentCreationOptions::REGISTERED_USERS
   */
  public static function getContentCreation(EntityInterface $entity): string {
    return $entity->{self::GROUP_CONTENT_CREATION[$entity->bundle()]}->first()->value;
  }

}

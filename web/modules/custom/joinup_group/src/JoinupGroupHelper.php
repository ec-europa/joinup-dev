<?php

declare(strict_types = 1);

namespace Drupal\joinup_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\rdf_entity\RdfInterface;

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
   * Returns whether the entity is one of the rdf groups.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is an rdf of bundle collection or solution, false
   *   otherwise.
   */
  public static function isGroup(EntityInterface $entity): bool {
    return $entity instanceof RdfInterface && isset(self::GROUP_BUNDLES[$entity->bundle()]);
  }

  /**
   * Returns whether the entity is an rdf collection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is an rdf of bundle collection, false otherwise.
   */
  public static function isCollection(EntityInterface $entity): bool {
    return self::isRdfEntityOfBundle($entity, 'collection');
  }

  /**
   * Returns whether the entity is an rdf solution.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is an rdf of bundle solution, false otherwise.
   */
  public static function isSolution(EntityInterface $entity): bool {
    return self::isRdfEntityOfBundle($entity, 'solution');
  }

  /**
   * Returns the group the entity belongs to.
   *
   * This relies on the fact that in Joinup every group entity only belongs to a
   * single group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to return the group.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The group entity, or NULL if the entity doesn't have a group.
   */
  public static function getGroup(EntityInterface $entity): ?EntityInterface {
    $group_field = self::getGroupField($entity);
    return $entity->get($group_field)->entity;
  }

  /**
   * Returns the name of the group field for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to return the group field name.
   *
   * @return string
   *   The field name.
   */
  public static function getGroupField(EntityInterface $entity): string {
    // Asset releases use the ADMS-AP dictated name for the group field, while
    // all others use the default name.
    return $entity->bundle() === 'asset_release' ? 'field_isr_is_version_of' : OgGroupAudienceHelperInterface::DEFAULT_FIELD;
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

  /**
   * Returns the workflow state for the given group.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The group for which to return the workflow state.
   *
   * @return string
   *   The workflow state.
   */
  public static function getState(EntityInterface $entity): string {
    return $entity->{self::GROUP_STATE_FIELDS[$entity->bundle()]}->first()->value;
  }

  /**
   * Returns whether the entity is an rdf entity of a specific bundle.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param string $bundle
   *   The bundle the entity should be.
   *
   * @return bool
   *   True if the entity is an rdf of bundle collection, false otherwise.
   */
  protected static function isRdfEntityOfBundle(EntityInterface $entity, $bundle): bool {
    return $entity instanceof RdfInterface && $entity->bundle() === $bundle;
  }

}

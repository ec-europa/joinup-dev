<?php

namespace Drupal\state_machine_revisions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the interface for revision managers.
 */
interface RevisionManagerInterface {

  /**
   * Returns the revision ID of the latest revision of the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   *
   * @return string|null
   *   The revision ID of the latest revision for the specified entity, or
   *   NULL if there is no such entity.
   */
  public function getLatestRevisionId(ContentEntityInterface $entity);

  /**
   * Determines if an entity has support for revisions.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   *
   * @return bool
   *   Whether or not the entity type has support for revisions.
   */
  public function isRevisionableEntityType(EntityTypeInterface $entityType);

  /**
   * Determines if an entity is a latest revision.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A revisionable content entity.
   *
   * @return bool
   *   TRUE if the specified object is the latest revision of its entity,
   *   FALSE otherwise.
   */
  public function isLatestRevision(ContentEntityInterface $entity);

  /**
   * Loads the revision marked as default for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The default entity revision or NULL if the entity type / entity doesn't
   *   exist.
   */
  public function loadDefaultRevision(ContentEntityInterface $entity);

  /**
   * Loads the latest revision of the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The latest entity revision or NULL if the entity type / entity doesn't
   *   exist.
   */
  public function loadLatestRevision(ContentEntityInterface $entity);

}

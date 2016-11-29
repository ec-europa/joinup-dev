<?php

namespace Drupal\state_machine_revisions;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Manages stuff.
 */
class RevisionManager implements RevisionManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RevisionManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevisionId($entityTypeID, $entityID) {
    if ($storage = $this->entityTypeManager->getStorage($entityTypeID)) {
      $revision_ids = $storage->getQuery()
        ->allRevisions()
        ->condition($this->entityTypeManager->getDefinition($entityTypeID)->getKey('id'), $entityID)
        ->sort($this->entityTypeManager->getDefinition($entityTypeID)->getKey('revision'), 'DESC')
        ->range(0, 1)
        ->execute();
      if ($revision_ids) {
        return array_keys($revision_ids)[0];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isRevisionableEntityType(EntityTypeInterface $entityType) {
    return $entityType->isRevisionable();
  }

  /**
   * {@inheritdoc}
   */
  public function isLatestRevision(ContentEntityInterface $entity) {
    return $entity->getRevisionId() == $this->getLatestRevisionId($entity->getEntityTypeId(), $entity->id());
  }

  /**
   * {@inheritdoc}
   */
  public function loadLatestRevision($entityTypeID, $entityID) {
    if ($latest_revision_id = $this->getLatestRevisionId($entityTypeID, $entityID)) {
      return $this->entityTypeManager->getStorage($entityTypeID)->loadRevision($latest_revision_id);
    }

    return NULL;
  }

}

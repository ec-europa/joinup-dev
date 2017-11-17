<?php

namespace Drupal\joinup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup\Controller\PinEntityController;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Helper class for Joinup.
 */
class JoinupHelper {

  /**
   * Checks if an entity is pinned inside a certain collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The rdf collection.
   *
   * @return bool
   *   True if the entity is pinned, false otherwise.
   */
  public static function isEntityPinned(ContentEntityInterface $entity, RdfInterface $collection) {
    if (self::isSolution($entity)) {
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      foreach ($entity->get(PinEntityController::SOLUTION_PIN_FIELD)->referencedEntities() as $rdf) {
        if ($rdf->id() === $collection->id()) {
          return TRUE;
        }
      }
    }
    elseif (self::isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the sticky status.
      /** @var \Drupal\node\NodeInterface $entity */
      return $entity->isSticky();
    }

    return FALSE;
  }

  /**
   * Sets the entity pinned status inside a certain collection.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The rdf collection.
   * @param bool $pinned
   *   TRUE to set the entity as pinned, FALSE otherwise.
   */
  public static function setEntityPinned(ContentEntityInterface $entity, RdfInterface $collection, bool $pinned) {
    if (self::isSolution($entity)) {
      $field = $entity->get(PinEntityController::SOLUTION_PIN_FIELD);
      if ($pinned) {
        $field->appendItem($collection->id());
      }
      else {
        $field->filter(function ($item) use ($collection) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
          return $item->target_id !== $collection->id();
        });
      }
    }
    elseif (self::isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the sticky status.
      /** @var \Drupal\node\NodeInterface $entity */
      $entity->setSticky($pinned);
    }

    $entity->save();
  }

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

<?php

namespace Drupal\joinup;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\node\NodeInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Helper class for Joinup.
 */
class JoinupHelper {

  /**
   * Returns whether the entity is an rdf collection.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is an rdf of bundle collection, false otherwise.
   */
  public static function isCollection(EntityInterface $entity) {
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
  public static function isSolution(EntityInterface $entity) {
    return self::isRdfEntityOfBundle($entity, 'solution');
  }

  /**
   * Returns whether the entity is a community content node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   True if the entity is a community content node, false otherwise.
   */
  public static function isCommunityContent(EntityInterface $entity) {
    return $entity instanceof NodeInterface && in_array($entity->bundle(), CommunityContentHelper::getBundles());
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
  protected static function isRdfEntityOfBundle(EntityInterface $entity, $bundle) {
    return $entity instanceof RdfInterface && $entity->bundle() === $bundle;
  }

}

<?php

namespace Drupal\joinup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for a pin service.
 */
interface PinServiceInterface {

  /**
   * Checks if an entity is pinned inside any collection or a specific one.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\rdf_entity\RdfInterface $collection
   *   The rdf collection where the entity should be pinned. Defaults to NULL,
   *   so the function will return TRUE if the entity is pinned in any
   *   collection.
   *
   * @return bool
   *   True if the entity is pinned, false otherwise.
   */
  public function isEntityPinned(ContentEntityInterface $entity, RdfInterface $collection = NULL);

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
  public function setEntityPinned(ContentEntityInterface $entity, RdfInterface $collection, bool $pinned);

  /**
   * Retrieves a list of collections where an entity is pinned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of collections where the content is pinned.
   */
  public function getCollectionsWherePinned(ContentEntityInterface $entity);

}

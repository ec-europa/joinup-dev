<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\rdf_entity\RdfInterface;

/**
 * Interface for a pin service.
 */
interface PinServiceInterface {

  /**
   * Checks if an entity is pinned inside any group or a specific one.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   * @param \Drupal\rdf_entity\RdfInterface|null $group
   *   The rdf group where the entity should be pinned. Defaults to NULL,
   *   so the function will return TRUE if the entity is pinned in any
   *   group.
   *
   * @return bool
   *   True if the entity is pinned, false otherwise.
   */
  public function isEntityPinned(ContentEntityInterface $entity, ?RdfInterface $group = NULL);

  /**
   * Sets the entity pinned status inside a certain group.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The rdf group.
   * @param bool $pinned
   *   TRUE to set the entity as pinned, FALSE otherwise.
   */
  public function setEntityPinned(ContentEntityInterface $entity, RdfInterface $group, bool $pinned);

  /**
   * Retrieves a list of groups where an entity is pinned.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity itself.
   *
   * @return \Drupal\rdf_entity\RdfInterface[]
   *   A list of groups where the content is pinned.
   */
  public function getGroupsWherePinned(ContentEntityInterface $entity);

}

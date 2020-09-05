<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;

/**
 * Interface for a pin service.
 */
interface PinServiceInterface {

  /**
   * Checks if an entity is pinned inside any group or a specific one.
   *
   * @param \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity
   *   The entity to check.
   * @param \Drupal\joinup_group\Entity\GroupInterface|null $group
   *   The rdf group where the entity should be pinned. Defaults to NULL,
   *   so the function will return TRUE if the entity is pinned in any
   *   group.
   *
   * @return bool
   *   True if the entity is pinned, false otherwise.
   */
  public function isEntityPinned(PinnableGroupContentInterface $entity, ?GroupInterface $group = NULL);

  /**
   * Sets the entity pinned status inside a certain group.
   *
   * @param \Drupal\joinup_group\Entity\PinnableGroupContentInterface $entity
   *   The entity itself.
   * @param \Drupal\joinup_group\Entity\GroupInterface $group
   *   The rdf group.
   * @param bool $pinned
   *   TRUE to set the entity as pinned, FALSE otherwise.
   */
  public function setEntityPinned(PinnableGroupContentInterface $entity, GroupInterface $group, bool $pinned);

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

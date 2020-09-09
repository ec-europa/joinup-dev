<?php

declare(strict_types = 1);

namespace Drupal\joinup;

use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;

/**
 * Interface for a pin service.
 */
interface PinServiceInterface {

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

}

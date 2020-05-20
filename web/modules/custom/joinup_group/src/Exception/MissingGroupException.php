<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Exception;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Exception thrown when the required reference to a group is missing.
 *
 * @see \Drupal\joinup_group\Entity\GroupContentInterface::getGroup()
 */
class MissingGroupException extends \Exception {

  /**
   * The entity missing a group.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Sets the group content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity missing a group.
   *
   * @return $this
   */
  public function setEntity(ContentEntityInterface $entity): self {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Gets the group content entity missing a group.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity missing a group.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

}

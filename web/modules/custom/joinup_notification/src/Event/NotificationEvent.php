<?php

namespace Drupal\joinup_notification\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A notification event class.
 */
class NotificationEvent extends Event {

  /**
   * The operation string.
   *
   * @var string
   */
  protected $operation;

  /**
   * The entity object.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Constructs a notification event object.
   *
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function __construct($operation, EntityInterface $entity) {
    $this->operation = $operation;
    $this->entity = $entity;
  }

  /**
   * Returns the operation string.
   *
   * @return string
   *   The operation string.
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * Sets the operation string.
   *
   * @param string $operation
   *   The operation string.
   */
  public function setOperation($operation) {
    $this->operation = $operation;
  }

  /**
   * Returns the entity object.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Sets the entity object.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

}

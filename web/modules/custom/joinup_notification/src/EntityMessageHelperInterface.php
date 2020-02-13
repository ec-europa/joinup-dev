<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\message\MessageInterface;

/**
 * Interface for services that assist in managing messages for entities.
 */
interface EntityMessageHelperInterface {

  /**
   * Constant representing sorting by ascending creation date.
   */
  const SORT_ASC = 'ASC';

  /**
   * Constant representing sorting by descending creation date.
   */
  const SORT_DESC = 'DESC';

  /**
   * Creates a new message that is associated with the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that will be referenced in the new message.
   * @param string $template
   *   The message template ID.
   * @param array $arguments
   *   The array of arguments that will be used to replace token-like strings in
   *   the message.
   * @param string $field_name
   *   The name of the field that references the entity for which the message is
   *   to be created.
   *
   * @return \Drupal\message\MessageInterface
   *   The newly created, unsaved, message.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the passed entity cannot be referenced. Possibly since it
   *   hasn't yet been saved and does not have an ID yet.
   */
  public function createMessage(EntityInterface $entity, string $template, array $arguments, string $field_name): MessageInterface;

  /**
   * Returns the messages that are associated with the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity that is referenced by the message.
   * @param string $template
   *   The message template ID.
   * @param string $field_name
   *   The name of the field that references the entity for which the message is
   *   to be created.
   * @param array $values
   *   An associative array of properties to filter the messages by, where the
   *   keys are the property names and the values are the values those
   *   properties must have.
   * @param int $limit
   *   The maximum number of entities to return. Defaults to 10 messages.
   * @param string $order
   *   Whether to sort by ascending or descending creation date. Defaults to
   *   descending order.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The corresponding messages.
   */
  public function getMessages(EntityInterface $entity, string $template, string $field_name, array $values = [], int $limit = 10, string $order = self::SORT_DESC): array;

  /**
   * Checks that the entity is suitable for being handled by the service.
   *
   * Typical use cases are to check that the entity has been saved and is of the
   * correct type and bundle.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the entity is not valid.
   */
  public function validateEntity(EntityInterface $entity): void;

  /**
   * Returns the entity storage for the Message entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  public function getMessageEntityStorage(): EntityStorageInterface;

}

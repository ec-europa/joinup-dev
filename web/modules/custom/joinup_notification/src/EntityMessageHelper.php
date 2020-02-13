<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\message\MessageInterface;

/**
 * Service that assists in handling messages for entities.
 *
 * This is intended to be used for creating and retrieving messages that are
 * related to entities.
 *
 * To send these messages, use the JoinupMessageDelivery service.
 *
 * There are different use cases for sending messages, this is only intended for
 * basic creation and retrieval of messages. In case your entity requires custom
 * business logic then feel free to inject this service and leverage it. See for
 * example the InvitationMessageHelper which implements default arguments and
 * resends a single message for each invitation instead of creating a new
 * message every time.
 *
 * Because of the wild variations in business logic regarding messages this was
 * designed as a standalone service rather than an base class or a trait because
 * it is impossible to come up with an API that will satisfy all use cases.
 *
 * @see \Drupal\joinup_invite\InvitationMessageHelper
 * @see \Drupal\joinup_notification\JoinupMessageDelivery
 */
class EntityMessageHelper implements EntityMessageHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new InvitationMessageHelper service.
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
  public function createMessage(EntityInterface $entity, string $template, array $arguments, string $field_name): MessageInterface {
    $this->validateEntity($entity);

    /** @var \Drupal\message\MessageInterface $message */
    $message = $this->getMessageEntityStorage()->create([
      'template' => $template,
      'arguments' => $arguments,
      $field_name => $entity->id(),
    ]);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(EntityInterface $entity, string $template, string $field_name, array $values = [], int $limit = 10, string $order = self::SORT_DESC): array {
    $this->validateEntity($entity);

    $query = $this->getMessageEntityStorage()->getQuery();
    $query->accessCheck(FALSE);
    $query->condition($field_name, $entity->id());
    $query->condition('template', $template);

    foreach ($values as $name => $value) {
      // Cast scalars to array so we can consistently use an IN condition.
      $query->condition($name, (array) $value, 'IN');
    }

    $query->range(0, $limit);
    $query->sort('created', $order);
    $result = $query->execute();

    return $result ? $this->getMessageEntityStorage()->loadMultiple($result) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateEntity(EntityInterface $entity): void {
    // Check that the entity has been saved, since we need to be able to
    // reference its ID.
    if ($entity->isNew()) {
      throw new \InvalidArgumentException('Messages can only be created for saved entities.');
    }
  }

  /**
   * Returns the entity storage for the Message entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  public function getMessageEntityStorage(): EntityStorageInterface {
    try {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('message');
    }
    catch (InvalidPluginDefinitionException $e) {
      // Since the Joinup Notification module depends on the Message module we
      // can reasonably expect that the Message entity type is available at
      // runtime and we don't need to handle the possibility that it is not
      // defined.
      // Instead of ignoring this silently, convert it in an (unchecked) runtime
      // exception. This will make sure that in the extremely rare case that
      // this exception might still be thrown, the error will still be logged,
      // but it saves us from having to document this 'impossible' exception all
      // the way up the call chain.
      throw new \RuntimeException('Entity storage for the Message entity type is not defined.', 0, $e);
    }

    return $storage;
  }

}

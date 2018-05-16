<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\message\MessageInterface;

/**
 * Base class for services that assist in handling messages for entities.
 */
abstract class EntityMessageHelperBase implements EntityMessageHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The helper service for delivering messages.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * Constructs a new InvitationMessageHelper service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $messageDelivery
   *   The helper service for delivering messages.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupMessageDeliveryInterface $messageDelivery) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messageDelivery = $messageDelivery;
  }

  /**
   * {@inheritdoc}
   */
  public function createMessage(EntityInterface $entity, string $template, array $arguments): MessageInterface {
    $this->validateEntity($entity);

    // Add default arguments.
    $arguments += $this->getDefaultArguments($entity);

    /** @var \Drupal\message\MessageInterface $message */
    $message = $this->getMessageEntityStorage()->create([
      'template' => $template,
      'arguments' => $arguments,
      'field_entity' => $entity->id(),
    ]);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessages(EntityInterface $entity, string $template, array $values = [], int $limit = 10, string $order = self::SORT_DESC): array {
    $this->validateEntity($entity);

    $messages = $this->getMessageEntityStorage()->loadByProperties([
      'template' => $template,
      'field_invitation' => $entity->id(),
    ]);

    return $messages ? reset($messages) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(EntityInterface $entity, MessageInterface $message): bool {
    return $this->messageDelivery
      ->setMessage($message)
      ->setRecipients([$entity->getRecipient()])
      ->sendMail();
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
  protected function getMessageEntityStorage(): EntityStorageInterface {
    try {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('message');
    }
    catch (InvalidPluginDefinitionException $e) {
      // Since the Joinup Notification module depends on the Message module we
      // can reasonably expect that the Message entity type is available at
      // runtime and we don't need to handle the possibility that it is not
      // defined.
      // Convert this in an (unchecked) runtime exception so we don't need to
      // document this 'impossible' exception all the way up the call chain.
      throw new \RuntimeException('Entity storage for the Message entity type is not defined.', 0, $e);
    }

    return $storage;
  }

  /**
   * Returns default arguments for entity messages.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to create the default arguments.
   *
   * @return array
   *   An associative array of default arguments, keyed by argument ID.
   */
  protected function getDefaultArguments(EntityInterface $entity): array {
    return [];
  }

}

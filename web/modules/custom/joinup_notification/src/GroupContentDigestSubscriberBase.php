<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\og\OgMembershipInterface;

/**
 * Provides a base class for the group content subscription subscriber classe.
 */
abstract class GroupContentDigestSubscriberBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Joinup message delivery service.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $joinupMessageDelivery
   *   The Joinup message delivery service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupMessageDeliveryInterface $joinupMessageDelivery, LoggerChannelFactoryInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messageDelivery = $joinupMessageDelivery;
    $this->loggerFactory = $logger;
  }

  /**
   * Notifies subscribed users when new community content is published.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifyOnCommunityContentCreation(NotificationEvent $event): void {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $event->getEntity();

    // Only notify if the newly created content is published.
    if (!$entity instanceof CollectionContentInterface || !$entity->isPublished()) {
      return;
    }

    $this->sendMessage($entity, $this->getTemplateId());
  }

  /**
   * Notifies subscribed users when existing community content is published.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifyOnCommunityContentPublication(NotificationEvent $event): void {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $event->getEntity();

    // Only notify if the content is being published for the first time.
    if (!$entity instanceof CollectionContentInterface || !$entity->isPublished() || empty($entity->original) || $entity->original->isPublished() || !$this->isFirstPublishedRevision($entity)) {
      return;
    }

    $this->sendMessage($entity, $this->getTemplateId());
  }

  /**
   * Returns the list of subscribers.
   *
   * @param \Drupal\joinup_group\Entity\GroupContentInterface $entity
   *   The group content entity for which to return the subscribers.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of subscribers as an array of user accounts, keyed by user ID.
   */
  protected function getSubscribers(GroupContentInterface $entity): array {
    $membership_storage = $this->entityTypeManager->getStorage('og_membership');
    $membership_ids = $membership_storage
      ->getQuery()
      ->condition('entity_type', 'rdf_entity')
      ->condition('entity_id', $this->getGroupId($entity))
      ->condition('state', OgMembershipInterface::STATE_ACTIVE)
      ->condition('subscription_bundles', $entity->bundle())
      ->execute();

    // We're loading the full membership entities in order to extract the user
    // IDs. Since subscriptions are disabled by default we do not expect them to
    // become hugely popular, but if they do this can possibly be optimized by
    // bypassing the entity API and doing a direct SELECT query.
    $memberships = $membership_storage->loadMultiple($membership_ids);
    $user_ids = array_map(function (OgMembershipInterface $membership): int {
      return (int) $membership->getOwnerId();
    }, $memberships);

    return $this->entityTypeManager->getStorage('user')->loadMultiple($user_ids);
  }

  /**
   * Sends the notification to the recipients.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The group content for which to send the notification.
   * @param string $message_template
   *   The ID of the message template to use.
   *
   * @return bool
   *   Whether or not the sending of the e-mails has succeeded.
   */
  protected function sendMessage(ContentEntityInterface $entity, string $message_template): bool {
    try {
      $success = TRUE;
      // Create individual messages for each subscriber so that we can honor the
      // user's chosen digest frequency.
      foreach ($this->getSubscribers($entity) as $subscriber) {
        $message_values = [
          $this->getTemplateFieldName() => [
            0 => [
              'target_type' => $entity->getEntityTypeId(),
              'target_id' => $entity->id(),
            ],
          ],
        ];
        $success = $this->messageDelivery->sendMessageTemplateToUser($message_template, [], $subscriber, [], $message_values, TRUE) && $success;
      }
      return $success;
    }
    catch (\Exception $e) {
      $context = ['exception' => $e];
      $this->loggerFactory->get('mail')->critical('Unexpected exception thrown when sending a collection content subscription message.', $context);
      return FALSE;
    }
  }

  /**
   * Returns whether the passed in entity is the first published revision.
   *
   * @param \Drupal\Core\Entity\EntityPublishedInterface|\Drupal\Core\Entity\RevisionableInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the current revision of the entity is the first published
   *   revision.
   */
  protected function isFirstPublishedRevision(EntityPublishedInterface $entity): bool {
    return $entity->getRevisionId() == $this->getFirstPublishedRevisionId($entity);
  }

  /**
   * Returns the ID of the first published revision of the given entity.
   *
   * @param \Drupal\Core\Entity\EntityPublishedInterface $entity
   *   The entity for which to return the first published revision ID.
   *
   * @return mixed|null
   *   The revision ID, or NULL if there is no published revision.
   */
  protected function getFirstPublishedRevisionId(EntityPublishedInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $definition = $this->entityTypeManager->getDefinition($entity_type);

    $revision_ids = $storage->getQuery()
      ->allRevisions()
      ->condition($definition->getKey('id'), $entity->id())
      ->condition($definition->getKey('published'), 1)
      ->sort($definition->getKey('revision'), 'ASC')
      ->range(0, 1)
      ->execute();
    reset($revision_ids);
    return !empty($revision_ids) ? key($revision_ids) : NULL;
  }

  /**
   * Returns the entity ID of the group the given entity belongs to.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which to return the group ID.
   *
   * @return string
   *   The collection or solution ID.
   */
  abstract protected function getGroupId(ContentEntityInterface $entity): string;

  /**
   * Returns the name of the field that references the entities in templates.
   *
   * @return string
   *   The field name.
   */
  abstract protected function getTemplateFieldName(): string;

  /**
   * Returns the template ID of the digest message.
   *
   * @return string
   *   The message template ID.
   */
  abstract protected function getTemplateId(): string;

}

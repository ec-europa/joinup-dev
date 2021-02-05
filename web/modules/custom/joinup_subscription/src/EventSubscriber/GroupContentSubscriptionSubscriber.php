<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\EventSubscriber;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\og\OgMembershipInterface;
use Drupal\solution\Entity\SolutionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for compiling group content subscription digest messages.
 */
class GroupContentSubscriptionSubscriber implements EventSubscriberInterface {

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
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      NotificationEvents::COMMUNITY_CONTENT_CREATE => 'notifyOnCommunityContentCreation',
      NotificationEvents::COMMUNITY_CONTENT_UPDATE => 'notifyOnCommunityContentPublication',
      NotificationEvents::RDF_ENTITY_CRUD => 'notifyOnRdfEntityCrudOperation',
    ];
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

    $this->sendMessage($entity, 'collection_content_subscription');
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

    $this->sendMessage($entity, 'collection_content_subscription');
  }

  /**
   * Notifies subscribed users when a new solution is added to the collection.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifyOnRdfEntityCrudOperation(NotificationEvent $event) {
    // Only act on entities that are being created or updated. Subscribers are
    // not notified about solutions that are being removed.
    if (!in_array($event->getOperation(), ['create', 'update'])) {
      return;
    }

    // We are only concerned about solutions that belong to a collection, are
    // published and are newly created or are being published for the first
    // time. Let's filter it down.
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $event->getEntity();
    if (
      !$entity instanceof SolutionInterface ||
      $entity->get('collection')->isEmpty() ||
      !$entity->isPublished() ||
      // Note: the `->hasPublished` property is a hack that will be removed once
      // we have revisionable RDF entities.
      // @see joinup_group_entity_presave()
      (!$entity->isNew() && $entity->hasPublished)
    ) {
      return;
    }

    $this->sendMessage($entity, 'collection_content_subscription');
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
      ->condition('entity_id', $entity->getGroupId())
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
   * @param \Drupal\joinup_group\Entity\GroupContentInterface $group_content
   *   The group content for which to send the notification.
   * @param string $message_template
   *   The ID of the message template to use.
   *
   * @return bool
   *   Whether or not the sending of the e-mails has succeeded.
   */
  protected function sendMessage(GroupContentInterface $group_content, string $message_template): bool {
    try {
      $success = TRUE;
      // Create individual messages for each subscriber so that we can honor the
      // user's chosen digest frequency.
      foreach ($this->getSubscribers($group_content) as $subscriber) {
        $message_values = [
          'field_collection_content' => [
            0 => [
              'target_type' => $group_content->getEntityTypeId(),
              'target_id' => $group_content->id(),
            ],
          ],
        ];
        $success = $this->messageDelivery->sendMessageTemplateToUser($message_template, [], $subscriber, [], $message_values, TRUE) && $success;
      }
      return $success;
    }
    catch (\Exception $e) {
      $context = ['exception' => $e];
      $this->loggerFactory->get('mail')->critical('Unexpected ' . get_class($e) . ' thrown in ' . $e->getFile() . ' on line ' . $e->getLine() . ' when sending a group content subscription message.', $context);
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

}

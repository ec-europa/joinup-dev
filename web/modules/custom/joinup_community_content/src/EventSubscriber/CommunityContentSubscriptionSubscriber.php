<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\node\NodeInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for compiling community content subscription digest messages .
 */
class CommunityContentSubscriptionSubscriber implements EventSubscriberInterface {

  /**
   * The Joinup subscribe service.
   *
   * @var \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface
   */
  protected $subscribeService;

  /**
   * The Joinup message delivery service.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * @param \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface $subscribe_service
   *   The Joinup subscribe service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  //public function __construct(JoinupDiscussionSubscriptionInterface $subscribe_service, JoinupMessageDeliveryInterface $message_delivery, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
  public function __construct(EntityTypeManagerInterface $entityTypeManager, JoinupMessageDeliveryInterface $joinupMessageDelivery, LoggerChannelFactoryInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messageDelivery = $joinupMessageDelivery;
    $this->loggerFactory = $logger;
    //$this->subscribeService = $subscribe_service;
    //$this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      NotificationEvents::COMMUNITY_CONTENT_CREATE => 'notifyOnCommunityContentCreation',
      NotificationEvents::COMMUNITY_CONTENT_UPDATE => 'notifyOnCommunityContentPublication',
    ];
  }

  /**
   * Notifies subscribed users when new community content is published.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifyOnCommunityContentCreation(NotificationEvent $event) {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $event->getEntity();

    // Only notify if the newly created content is published.
    if (!$entity->isPublished()) {
      return;
    }

    $this->sendMessage($entity, 'community_content_subscription');
  }

  /**
   * Notifies subscribed users when existing community content is published.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifyOnCommunityContentPublication(NotificationEvent $event) {
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $event->getEntity();

    // Only notify if the content is being published.
    // @todo We should also check if the content has been published in the past,
    //   so we do not accidentally send notifications if content is republished.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4980
    if (!$entity->isPublished() || empty($entity->original) || $entity->original->isPublished()) {
      return;
    }

    $this->sendMessage($entity, 'community_content_subscription');
  }

  /**
   * Returns the list of subscribers.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The community content entity for which to return the subscribers.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of subscribers as an array of user accounts, keyed by user ID.
   */
  protected function getSubscribers(NodeInterface $entity): array {
    $group_id = $entity->get('og_audience')->target_id;
    $group_entity_type = $entity->getFieldDefinition('og_audience')->getSetting('target_type');
    $membership_storage = $this->entityTypeManager->getStorage('og_membership');
    $membership_ids = $membership_storage
      ->getQuery()
      ->condition('entity_type', $group_entity_type)
      ->condition('entity_id', $group_id)
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
   * @param \Drupal\node\NodeInterface $community_content
   *   The discussion for which to send the notification.
   * @param string $message_template
   *   The ID of the message template to use.
   *
   * @return bool
   *   Whether or not the sending of the e-mails has succeeded.
   */
  protected function sendMessage(NodeInterface $community_content, string $message_template): bool {
    try {
      $success = TRUE;
      // Create individual messages for each subscriber so that we can honor the
      // user's chosen digest frequency.
      foreach ($this->getSubscribers($community_content) as $subscriber) {
        $notifier_options = [
          'entity_type' => $community_content->getEntityTypeId(),
          'entity_id' => $community_content->id(),
        ];
        $success = $this->messageDelivery->sendMessageTemplateToUser($message_template, [], $subscriber, $notifier_options) && $success;
      }
      return $success;
    }
    catch (\Exception $e) {
      $context = ['exception' => $e];
      $this->loggerFactory->get('mail')->critical('Unexpected exception thrown when sending a community content subscription message.', $context);
      return FALSE;
    }
  }

}

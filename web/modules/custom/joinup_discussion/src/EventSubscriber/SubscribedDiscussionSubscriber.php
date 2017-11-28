<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\joinup_discussion\Event\DiscussionEvent;
use Drupal\joinup_discussion\Event\DiscussionEvents;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to discussion CRUD operations.
 */
class SubscribedDiscussionSubscriber implements EventSubscriberInterface {

  /**
   * The Joinup subscribe service.
   *
   * @var \Drupal\joinup_subscription\JoinupSubscriptionInterface
   */
  protected $subscribeService;

  /**
   * The Joinup message delivery service.
   *
   * @var \Drupal\joinup_notification\JoinupMessageDeliveryInterface
   */
  protected $messageDelivery;

  /**
   * The comment entity.
   *
   * @var \Drupal\comment\CommentInterface
   */
  protected $comment;

  /**
   * The community content discussion node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $discussion;

  /**
   * The discussion parent group.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $group;

  /**
   * The list of recipients as user accounts.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $recipients;

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
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\joinup_subscription\JoinupSubscriptionInterface $subscribe_service
   *   The Joinup subscribe service.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $message_delivery
   *   The Joinup message delivery service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(JoinupSubscriptionInterface $subscribe_service, JoinupMessageDeliveryInterface $message_delivery, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->subscribeService = $subscribe_service;
    $this->messageDelivery = $message_delivery;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      DiscussionEvents::UPDATE => 'onDiscussionUpdate',
      DiscussionEvents::DISCUSSION_DELETED => 'notifyOnDiscussionDeletion',
    ];
  }

  /**
   * Reacts when a discussion is updated.
   *
   * @param \Drupal\joinup_discussion\Event\DiscussionEvent $event
   *   The event object.
   */
  public function onDiscussionUpdate(DiscussionEvent $event): void {
    $this->discussion = $event->getNode();

    // Don't handle inconsistent discussions, without a parent group.
    if (!$this->group = $this->discussion->get('og_audience')->entity) {
      return;
    }

    // No recipients, no reaction.
    if (!$this->getRecipients()) {
      return;
    }

    $this->messageDelivery
      ->createMessage('discussion_updated')
      ->setArguments($this->getArguments())
      ->setRecipients($this->getRecipients())
      ->sendMail();
  }

  /**
   * Sends notification to subscribed users when a discussion is deleted.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event object.
   */
  public function notifyOnDiscussionDeletion(NotificationEvent $event) : void {
    /** @var \Drupal\node\NodeInterface $discussion */
    $discussion = $event->getEntity();
    $this->sendMessage($discussion);
  }

  /**
   * Returns the list of recipients.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of recipients as an array of user accounts, keyed by user ID.
   */
  protected function getRecipients(): array {
    if (!isset($this->recipients)) {
      $this->recipients = $this->subscribeService->getSubscribers($this->discussion, 'subscribe_discussions');
      // The author of the discussion update should not be notified, if
      // eventually he/she is in the subscribers list.
      if (!$this->discussion->getRevisionUser()->isAnonymous()) {
        unset($this->recipients[$this->discussion->getRevisionUserId()]);
      }
    }
    return $this->recipients;
  }

  /**
   * Returns the list of subscribers.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to return the subscribers.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of subscribers as an array of user accounts, keyed by user ID.
   */
  protected function getSubscribers(NodeInterface $discussion) : array {
    $subscribers = [];

    // The discussion owner is added to the list of subscribers.
    $owner = $discussion->getOwner();
    if (!empty($owner) && !$owner->isAnonymous()) {
      $subscribers[$owner->id()] = $owner;
    }
    $subscribers += $this->subscribeService->getSubscribers($discussion, 'subscribe_discussions');

    // Exclude the current user from the list.
    $current_user = $this->getCurrentUser();
    return array_filter($subscribers, function (AccountInterface $subscriber) use ($current_user) {
      return $subscriber->id() != $current_user->id();
    });
  }

  /**
   * Builds the message arguments.
   */
  protected function getArguments(): array {
    return [
      '@entity:title' => $this->discussion->label(),
      '@group:label' => $this->group->label(),
      '@group:bundle' => $this->group->bundle(),
      '@entity:url' => $this->discussion->toUrl('canonical', [
        'absolute' => TRUE,
      ])->toString(),
    ];
  }

  /**
   * Returns the message arguments.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to return the arguments.
   *
   * @return array
   *   An associative array of message arguments, keyed by argument ID.
   */
  protected function getArguments(NodeInterface $discussion) : array {
    $arguments = [];

    $arguments['@entity:title'] = $discussion->label();

    $actor = $this->getCurrentUser();
    $actor_first_name = !empty($actor->get('field_user_first_name')->first()->value) ? $actor->get('field_user_first_name')->first()->value : '';
    $actor_family_name = !empty($actor->get('field_user_family_name')->first()->value) ? $actor->get('field_user_family_name')->first()->value : '';

    if ($actor->hasRole('moderator')) {
      $arguments['@actor:full_name'] = 'The Joinup Support Team';
    }
    else {
      $arguments['@actor:full_name'] = empty($actor->get('full_name')->value) ? $actor_first_name . ' ' . $actor_family_name : $actor->get('full_name')->value;
    }

    return $arguments;
  }

  /**
   * Returns the fully loaded User entity for the current user.
   *
   * @return \Drupal\user\UserInterface
   *   The current user.
   */
  protected function getCurrentUser() : UserInterface {
    return $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
  }

  /**
   * Sends the notification to the recipients.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to send the notification.
   *
   * @return bool
   *   Whether or not the sending of the e-mails has succeeded.
   */
  protected function sendMessage(NodeInterface $discussion) : bool {
    return $this->messageDelivery
      ->createMessage('discussion_delete')
      ->setArguments($this->getArguments($discussion))
      ->setRecipients($this->getSubscribers($discussion))
      ->sendMail();
  }

}

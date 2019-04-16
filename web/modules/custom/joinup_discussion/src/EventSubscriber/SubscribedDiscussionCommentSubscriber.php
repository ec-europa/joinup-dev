<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_notification\MessageArgumentGenerator;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to comment CRUD operations.
 */
class SubscribedDiscussionCommentSubscriber implements EventSubscriberInterface {

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
   * The comment parent discussion node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $discussion;

  /**
   * The comment parent discussion group.
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
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\joinup_subscription\JoinupSubscriptionInterface $subscribe_service
   *   The Joinup subscribe service.
   * @param \Drupal\joinup_notification\JoinupMessageDeliveryInterface $message_delivery
   *   The Joinup message delivery service.
   */
  public function __construct(JoinupSubscriptionInterface $subscribe_service, JoinupMessageDeliveryInterface $message_delivery) {
    $this->subscribeService = $subscribe_service;
    $this->messageDelivery = $message_delivery;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [NotificationEvents::COMMENT_CRUD => 'commentCrudProxy'];
  }

  /**
   * Acts as a proxy by passing the control to a dedicated method.
   *
   * Normally we should have split the events into more specific events, like
   * 'comment create', 'comment update', 'comment delete'. Just 'comment crud'
   * is too generic and is not actually an event. In order to keep the
   * compatibility with Joinup Notification module, we use this proxy method to
   * delegate the reaction to a specific protected method, based on the real
   * operation being performed.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The notification event object.
   */
  public function commentCrudProxy(NotificationEvent $event): void {
    // @todo We shouldn't rely on data stored in local properties. This service
    //   persists on the dependency injection container and might contain stale
    //   data. Instead the Comment entity should be passed to any other methods
    //   that require it.
    // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-4253
    $this->comment = $event->getEntity();

    // Discussion comments are 'reply' comment types.
    if ($this->comment->bundle() !== 'reply') {
      return;
    }

    // Orphan comment?
    if (!$this->discussion = $this->comment->getCommentedEntity()) {
      return;
    }

    // Don't handle inconsistent discussions, without a parent group.
    if (!$this->group = $this->discussion->get('og_audience')->entity) {
      return;
    }

    // No recipients, no reaction.
    if (!$this->getRecipients()) {
      return;
    }

    // Call the appropriate method.
    switch ($event->getOperation()) {
      case 'create':
        $this->onCommentCreate();
        break;

      case 'update':
        $this->onCommentUpdate();
        break;
    }
  }

  /**
   * Reacts when a new comment is added to a discussion.
   */
  protected function onCommentCreate(): void {
    // Do not send the notification if the comment is not yet published. This
    // normally happens when an anonymous user is posting a comment because such
    // comments are subject of approval.
    if ($this->comment->isPublished()) {
      $this->sendMessage();
    }
  }

  /**
   * Reacts when a new discussion comment is updated.
   */
  protected function onCommentUpdate(): void {
    // Notifications for anonymous comments are not sent on comment creation but
    // when the comment is approved/published.
    if ($this->comment->getOwner()->isAnonymous()) {
      /** @var \Drupal\comment\CommentInterface $original_comment */
      $original_comment = $this->comment->original;
      // An anonymous comment has been just approved.
      if (!$original_comment->isPublished() && $this->comment->isPublished()) {
        $this->sendMessage();
      }
    }
  }

  /**
   * Returns the list of recipients.
   *
   * @return \Drupal\user\UserInterface[]
   *   The list of recipients as an array of user accounts, keyed by user ID.
   */
  protected function getRecipients(): array {
    if (is_null($this->recipients)) {
      $this->recipients = [
        // The discussion owner is added to the list of subscribers. We don't
        // check if the author is anonymous as this is handled by the message
        // delivery service.
        $this->discussion->getOwnerId() => $this->discussion->getOwner(),
      ] + $this->subscribeService->getSubscribers($this->discussion, 'subscribe_discussions');

      // The non-anonymous author of the comment should not be notified, if
      // eventually they are in the subscribers list.
      if (!$this->comment->getOwner()->isAnonymous()) {
        unset($this->recipients[$this->comment->getOwnerId()]);
      }
    }
    return $this->recipients;
  }

  /**
   * Builds the message arguments.
   */
  protected function getArguments(): array {
    return [
      '@comment:author:username' => $this->comment->getOwner()->getDisplayName(),
      '@entity:title' => $this->discussion->label(),
      '@entity:url' => $this->discussion->toUrl('canonical', [
        'absolute' => TRUE,
        'fragment' => "comment-{$this->comment->id()}",
      ])->toString(),
    ] + MessageArgumentGenerator::getGroupArguments($this->group);
  }

  /**
   * Sends the notification to the recipients.
   *
   * @return bool
   *   Whether or not the sending of the e-mails has succeeded.
   */
  protected function sendMessage(): bool {
    $success = TRUE;
    // Create individual messages for each subscriber so that we can honor the
    // user's chosen digest frequency.
    foreach ($this->getRecipients() as $recipient) {
      if ($recipient->isAnonymous()) {
        continue;
      }
      $notifier_options = [
        'entity_type' => $this->discussion->getEntityTypeId(),
        'entity_id' => $this->discussion->id(),
      ];
      $success = $this->messageDelivery->sendMessageTemplateToUser('discussion_comment_new', $this->getArguments(), $recipient, $notifier_options, TRUE) && $success;
    }
    return $success;
  }

}

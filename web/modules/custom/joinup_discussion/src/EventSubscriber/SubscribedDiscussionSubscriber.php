<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\joinup_community_content\Event\CommunityContentEvent;
use Drupal\joinup_community_content\Event\CommunityContentEvents;
use Drupal\joinup_notification\JoinupMessageDeliveryInterface;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
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
    return [CommunityContentEvents::DISCUSSION_UPDATE => 'onDiscussionUpdate'];
  }

  /**
   * Reacts when a discussion is updated.
   *
   * @param \Drupal\joinup_community_content\Event\CommunityContentEvent $event
   *   The notification event object.
   */
  public function onDiscussionUpdate(CommunityContentEvent $event): void {
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

      // The author of the discussion update should not be notified, if
      // eventually he/she is in the subscribers list.
      if (!$this->discussion->getRevisionUser()->isAnonymous()) {
        unset($this->recipients[$this->discussion->getRevisionUserId()]);
      }
    }
    return $this->recipients;
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

}

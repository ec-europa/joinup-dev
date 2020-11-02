<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\Event\InvitationEventInterface;
use Drupal\joinup_invite\Event\InvitationEvents;
use Drupal\joinup_subscription\Exception\UserAlreadySubscribedException;
use Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber handling invitations to discussions.
 */
class InvitationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The subscription service.
   *
   * @var \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface
   */
  protected $joinupSubscription;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new InvitationSubscriber.
   *
   * @param \Drupal\joinup_subscription\JoinupDiscussionSubscriptionInterface $joinupSubscription
   *   The subscription service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(JoinupDiscussionSubscriptionInterface $joinupSubscription, MessengerInterface $messenger) {
    $this->joinupSubscription = $joinupSubscription;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[InvitationEvents::NOT_PENDING_EVENT] = ['notPendingInvitation'];
    $events[InvitationEvents::ACCEPT_INVITATION_EVENT] = ['acceptInvitation'];
    $events[InvitationEvents::REJECT_INVITATION_EVENT] = ['rejectInvitation'];

    return $events;
  }

  /**
   * Invitation is already accepted or rejected.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function notPendingInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'discussion') {
      return;
    }

    if ($invitation->getStatus() === InvitationInterface::STATUS_ACCEPTED) {
      $this->messenger->addMessage($this->t('You were already subscribed to this discussion.'));
    }
    elseif ($invitation->getStatus() === InvitationInterface::STATUS_REJECTED) {
      $this->messenger->addMessage($this->t('You have already rejected the invitation to this discussion.'));
    }
  }

  /**
   * Accepts invitations to discussions.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function acceptInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'discussion') {
      return;
    }

    // After an invitation to participate in a discussion has been accepted we
    // should subscribe the user and show them a success message.
    try {
      $result = $this->joinupSubscription->subscribe($invitation->getRecipient(), $invitation->getEntity(), 'subscribe_discussions');
      if ($result) {
        $this->messenger->addMessage($this->t('You are now following this discussion.'));
      }
      else {
        $this->messenger->addMessage($this->t('Your subscription request could not be processed. Please try again later.'));
      }
    }
    catch (UserAlreadySubscribedException $e) {
      // This should never happen unless there is a race condition.
      $this->messenger->addMessage($this->t('You were already subscribed to this discussion.'));
    }
  }

  /**
   * Rejects invitations to discussions.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function rejectInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'discussion') {
      return;
    }

    // After an invitation to participate in a discussion has been rejected we
    // should unsubscribe the user and show them a success message.
    $this->joinupSubscription->unsubscribe($invitation->getRecipient(), $invitation->getEntity(), 'subscribe_discussions');
    $this->messenger->addMessage($this->t('You have rejected the invitation to this discussion.'));
  }

}

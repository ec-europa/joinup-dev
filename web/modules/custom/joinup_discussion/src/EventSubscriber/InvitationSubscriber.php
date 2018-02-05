<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_invite\Event\InvitationEventInterface;
use Drupal\joinup_invite\Event\InvitationEvents;
use Drupal\joinup_subscription\Exception\UserAlreadySubscribedException;
use Drupal\joinup_subscription\JoinupSubscriptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber handling invitations to discussions.
 */
class InvitationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The subscription service.
   *
   * @var \Drupal\joinup_subscription\JoinupSubscriptionInterface
   */
  protected $joinupSubscription;

  /**
   * Constructs a new InvitationSubscriber.
   *
   * @param \Drupal\joinup_subscription\JoinupSubscriptionInterface $joinupSubscription
   *   The subscription service.
   */
  public function __construct(JoinupSubscriptionInterface $joinupSubscription) {
    $this->joinupSubscription = $joinupSubscription;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[InvitationEvents::ACCEPT_INVITATION_EVENT] = ['acceptInvitation'];
    $events[InvitationEvents::REJECT_INVITATION_EVENT] = ['rejectInvitation'];

    return $events;
  }

  /**
   * Accepts invitations to discussions.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function acceptInvitation(InvitationEventInterface $event) : void {
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
        drupal_set_message($this->t('You have been subscribed to this discussion.'));
      }
      else {
        drupal_set_message($this->t('Your subscription request could not be processed. Please try again later.'));
      }
    }
    catch (UserAlreadySubscribedException $e) {
      drupal_set_message($this->t('You were already subscribed to this discussion.'));
    }
  }

  /**
   * Rejects invitations to discussions.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function rejectInvitation(InvitationEventInterface $event) : void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'discussion') {
      return;
    }

    // After an invitation to participate in a discussion has been rejected we
    // should unsubscribe the user and show them a success message.
    $this->joinupSubscription->unsubscribe($invitation->getRecipient(), $invitation->getEntity(), 'subscribe_discussions');
    drupal_set_message($this->t('You have rejected the invitation to this discussion.'));
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_invite\Event\InvitationEventInterface;
use Drupal\joinup_invite\Event\InvitationEvents;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgMembershipInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber handling invitations to groups.
 */
class GroupInvitationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs a new InvitationSubscriber.
   *
   * @param \Drupal\og\MembershipManagerInterface $membershipManager
   *   The membership manager service.
   */
  public function __construct(MembershipManagerInterface $membershipManager) {
    $this->membershipManager = $membershipManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[InvitationEvents::ACCEPT_INVITATION_EVENT] = ['acceptInvitation'];
    $events[InvitationEvents::REJECT_INVITATION_EVENT] = ['rejectInvitation'];

    return $events;
  }

  /**
   * Accepts invitations to join a group.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function acceptInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'group') {
      return;
    }

    $user = $invitation->getRecipient();
    $group = $invitation->getEntity();

    // We don't need to act if another action has been taken on the membership.
    $membership = $this->membershipManager->getMembership($group, $user, [OgMembershipInterface::STATE_PENDING]);
    if (empty($membership)) {
      drupal_set_message($this->t('There is no action pending for this user.'));
    }
    else {
      // Disable notifications related to memberships.
      $membership->skip_notification = TRUE;
      $membership->setState(OgMembershipInterface::STATE_ACTIVE)->save();
      $facilitator_id = $group->getEntityTypeId() . '-' . $group->bundle() . '-facilitator';
      $role_argument = $membership->hasRole($facilitator_id) ? $this->t('facilitator') : $this->t('member');
      drupal_set_message($this->t('You are now a @role of the "@title" @bundle.', [
        '@role' => $role_argument,
        '@title' => $group->label(),
        '@bundle' => $group->get('rid')->entity->getSingularLabel(),
      ]));
    }
  }

  /**
   * Rejects invitations to join a group.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function rejectInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'group') {
      return;
    }

    $user = $invitation->getRecipient();
    $group = $invitation->getEntity();

    // If the status is not pending, some other action has been taken and the
    // invitation has not been updated.
    $membership = $this->membershipManager->getMembership($group, $user, [OgMembershipInterface::STATE_PENDING]);
    if (!empty($membership)) {
      drupal_set_message($this->t('The invitation has been rejected. Thank you for your feedback.'));

      // We do not delete the membership as it will happen automatically.
      // @see: joinup_invite_invitation_delete().
      $invitation->delete();
    }
  }

}

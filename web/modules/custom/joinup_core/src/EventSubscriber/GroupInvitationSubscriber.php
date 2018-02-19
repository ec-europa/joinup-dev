<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\EventSubscriber;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
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

    if ($this->getMembership($group, $user)) {
      drupal_set_message($this->t('There is no action pending for this user.'));
      // If a user already has a membership and is here, it means that there was
      // a leftover pending invitation (the subscriber is called after a pending
      // invitation is accepted). In that case, delete the invitation. The
      // reason behind it is that if the invitation is not deleted, it might
      // falsely seem that the membership exists due to an accepted invitation.
      $invitation->delete();
    }
    else {
      $membership = $this->membershipManager->createMembership($group, $user);
      if (!empty($invitation->get('invitation_role')->first())) {
        $role = $invitation->get('invitation_role')->first()->entity;
        $membership->addRole($role);
      }
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

    // If there is already a membership, it means that it has been created
    // somewhere else in the meantime.
    if (!empty($this->getMembership($group, $user))) {
      drupal_set_message($this->t('There is no action pending for this user.'));
    }
    else {
      drupal_set_message($this->t('The invitation has been rejected. Thank you for your feedback.'));
      $invitation->delete();
    }
  }

  /**
   * Loads a membership regardless of its state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user entity.
   *
   * @return \Drupal\og\OgMembershipInterface|null
   *   An og membership.
   */
  protected function getMembership(EntityInterface $group, AccountInterface $user) {
    return $this->membershipManager->getMembership($group, $user, [
      OgMembershipInterface::STATE_ACTIVE,
      OgMembershipInterface::STATE_PENDING,
      OgMembershipInterface::STATE_BLOCKED,
    ]);
  }

}

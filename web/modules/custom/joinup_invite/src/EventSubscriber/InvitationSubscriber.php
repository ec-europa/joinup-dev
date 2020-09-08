<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\Event\InvitationEventInterface;
use Drupal\joinup_invite\Event\InvitationEvents;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber handling invitations to groups.
 */
class InvitationSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new InvitationSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The subscription service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MembershipManagerInterface $membership_manager, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipManager = $membership_manager;
    $this->messenger = $messenger;
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
   * Accepts invitations to groups.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function acceptInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();

    // Ignore invitations to other content entities.
    if ($invitation->bundle() !== 'group_membership') {
      return;
    }

    $role_id = $invitation->getExtraData()['role_id'];
    /** @var \Drupal\og\OgRoleInterface $role */
    $role = $this->entityTypeManager->getStorage('og_role')->load($role_id);
    $membership = $this->membershipManager->getMembership($invitation->getEntity(), $invitation->getRecipientId());
    if (empty($membership)) {
      $membership = $this->membershipManager->createMembership($invitation->getEntity(), $invitation->getRecipient());
    }
    $membership->addRole($role);
    $membership->save();

    $invitation->setStatus(InvitationInterface::STATUS_ACCEPTED)->save();
    $this->messenger->addMessage($this->t('%role role has been added to your membership due to accepting the invitation.', [
      '%role' => $role->label(),
    ]));
  }

  /**
   * Rejects invitations to the group.
   *
   * @param \Drupal\joinup_invite\Event\InvitationEventInterface $event
   *   The event that was fired.
   */
  public function rejectInvitation(InvitationEventInterface $event): void {
    $invitation = $event->getInvitation();
    if ($invitation->bundle() !== 'group_membership') {
      return;
    }

    $invitation->setStatus(InvitationInterface::STATUS_REJECTED)->save();
    $this->messenger->addMessage($this->t('You have rejected the invitation.'));
  }

}

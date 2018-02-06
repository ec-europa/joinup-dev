<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\Event\InvitationEvent;
use Drupal\joinup_invite\Event\InvitationEventInterface;
use Drupal\joinup_invite\Event\InvitationEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that accepts a user's invitation to a content entity.
 *
 * It is possible for certain users such as content owners, facilitators and
 * moderators to invite any user to look at certain content. The user will
 * receive an e-mail with an invitation link. The link leads to this page, which
 * will accept (or reject) the invitation. It also changes the status of the
 * invitation from 'pending' to 'accepted' (or 'rejected'). This data is stored
 * in the Message entity that was used to send the notification.
 *
 * For an example implementation, see the InviteToDiscussionForm.
 *
 * @see \Drupal\joinup_discussion\Form\InviteToDiscussionForm
 */
class InvitationController extends ControllerBase {

  /**
   * The identifier for accepting an invitation.
   *
   * @var string
   */
  const ACTION_ACCEPT = 'accept';

  /**
   * The identifier for rejecting an invitation.
   *
   * @var string
   */
  const ACTION_REJECT = 'reject';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs an InvitationController object.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('event_dispatcher'));
  }

  /**
   * Accepts or rejects an invitation.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation which is being accepted or rejected.
   * @param string $action
   *   The action which is being taken. Can be either 'accept' or 'reject'.
   * @param string $hash
   *   The hash value to protect against brute forcing invitations.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function updateInvitation(InvitationInterface $invitation, string $action, string $hash) : RedirectResponse {
    switch ($action) {
      case self::ACTION_ACCEPT:
        $invitation->accept()->save();
        $this->eventDispatcher->dispatch(InvitationEvents::ACCEPT_INVITATION_EVENT, $this->getEvent($invitation, $action));
        break;

      case self::ACTION_REJECT:
        $invitation->reject()->save();
        $this->eventDispatcher->dispatch(InvitationEvents::REJECT_INVITATION_EVENT, $this->getEvent($invitation, $action));
        break;

      default:
        throw new \InvalidArgumentException("Unknow action '$action'.");
    }

    $url = $invitation->getEntity()->toUrl();
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Access check for the invitation route.
   *
   * Access is granted only if the hash value is correct. This allows users to
   * accept the invitation even if they are not currently logged in to the
   * website.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation which is being accepted or rejected.
   * @param string $action
   *   The action which is being taken. Can be either 'accept' or 'reject'.
   * @param string $hash
   *   The hash value to check.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(InvitationInterface $invitation, string $action, string $hash) : AccessResultInterface {
    $valid_action = in_array($action, [self::ACTION_ACCEPT, self::ACTION_REJECT]);
    return AccessResult::allowedIf($valid_action && static::generateHash($invitation, $action) === $hash);
  }

  /**
   * Returns a unique hash based on the invitation and action.
   *
   * This protects the invitations from being brute forced.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation.
   * @param string $action
   *   The action that is being taken, either 'accept' or 'reject'.
   *
   * @return string
   *   A unique hash consisting of 8 lowercase alphanumeric characters, dashes
   *   and underscores.
   */
  public static function generateHash(InvitationInterface $invitation, string $action) : string {
    $data = $invitation->id();
    $data .= $action;
    return strtolower(substr(Crypt::hmacBase64($data, Settings::getHashSalt()), 0, 8));
  }

  /**
   * Returns an InvitationEvent for the given invitation and action.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation for which to create an event.
   * @param string $action
   *   The action that has been taken on the invitation.
   *
   * @return \Drupal\joinup_invite\Event\InvitationEventInterface
   *   The event.
   */
  protected function getEvent(InvitationInterface $invitation, string $action) : InvitationEventInterface {
    return (new InvitationEvent())
      ->setInvitation($invitation)
      ->setAction($action);
  }

}

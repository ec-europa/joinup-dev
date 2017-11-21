<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\joinup_invite\Entity\InvitationInterface;
use Drupal\joinup_invite\InvitationMessageHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller that accepts a user's invitation to a discussion.
 *
 * It is possible for discussion owners, facilitators and moderators to invite
 * any user to participate in a discussion. The user will receive an e-mail with
 * an invitation link. The link leads to this page, which will automatically
 * subscribe the user to the discussion. It also changes the status of the
 * invitation from 'pending' to 'accepted'. This data is stored in the Message
 * entity that was used to send the notification.
 *
 * @see \Drupal\joinup_invite\Form\InviteToDiscussionForm
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
   * The helper service for creating and retrieving messages for invitations.
   *
   * @var \Drupal\joinup_invite\InvitationMessageHelperInterface
   */
  protected $invitationMessageHelper;

  /**
   * Constructs an InvitationController object.
   *
   * @param \Drupal\joinup_invite\InvitationMessageHelperInterface $invitationMessageHelper
   *   The helper service for creating and retrieving messages for invitations.
   */
  public function __construct(InvitationMessageHelperInterface $invitationMessageHelper) {
    $this->invitationMessageHelper = $invitationMessageHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_invite.invitation_message_helper')
    );
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
    $action === self::ACTION_ACCEPT ? $invitation->accept() : $invitation->reject();
    $invitation->save();

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
   * This protects the discussions invitations from being brute forced.
   *
   * @param \Drupal\joinup_invite\Entity\InvitationInterface $invitation
   *   The invitation.
   * @param string $action
   *   The action that is being taken, either 'accept' or 'reject'.
   *
   * @return string
   *   A unique hash consisting of 8 lowercase alphanumeric characters.
   */
  public static function generateHash(InvitationInterface $invitation, string $action) : string {
    $data = $invitation->id() ;
    $data .= $action;
    return strtolower(substr(Crypt::hmacBase64($data, Settings::getHashSalt()), 0, 8));
  }

}

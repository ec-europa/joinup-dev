<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\joinup_invite\EventSubscriber\InvitationSubscriber;
use Drupal\joinup_invite\InviteToDiscussionHelper;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class AcceptDiscussionInvitationController extends ControllerBase {

  /**
   * The service that assists with inviting users to participate in discussions.
   *
   * @var \Drupal\joinup_invite\InviteToDiscussionHelper
   */
  protected $discussionHelper;

  /**
   * Constructs an AcceptDiscussionInvitationController.
   *
   * @param \Drupal\joinup_invite\InviteToDiscussionHelper $discussionHelper
   *   The service that assists in inviting users to participate in discussions.
   */
  public function __construct(InviteToDiscussionHelper $discussionHelper) {
    $this->discussionHelper = $discussionHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_invite.discussion_helper')
    );
  }

  /**
   * Route callback that sets the entity field to the specified value.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The discussion to which the user was invited.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user which was invited.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function acceptInvitation(NodeInterface $node, AccountInterface $user) {
    $message = $this->discussionHelper->getInvitationMessage($node, $user);
    $message->set('field_invitation_status', InvitationSubscriber::INVITATION_STATUS_ACCEPTED);
    $message->save();

    drupal_set_message($this->t('You have been subscribed to this discussion.'));

    $url = $node->toUrl();
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }

  /**
   * Access check for the invitation route.
   *
   * Access is granted only if the hash value is correct. This allows users to
   * accept the invitation even if they are not currently logged in to the
   * website.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The discussion the user is being invited to.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user that is being invited to the discussion.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(NodeInterface $node, AccountInterface $user, string $hash) : AccessResultInterface {
    return AccessResult::allowedIf(static::generateHash($node, $user) === $hash);
  }

  /**
   * Returns a unique hash based on the discussion and users.
   *
   * This protects the discussions invitations from being brute forced.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The discussion the user is being invited to.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user that is being invited to the discussion.
   *
   * @return string
   *   A unique hash consisting of 8 lowercase alphanumeric characters.
   */
  public static function generateHash(NodeInterface $node, AccountInterface $user) : string {
    $data = $node->id();
    $data .= $user->id();
    $data .= $user->getEmail();
    return strtolower(substr(Crypt::hmacBase64($data, Settings::getHashSalt()), 0, 8));
  }

}

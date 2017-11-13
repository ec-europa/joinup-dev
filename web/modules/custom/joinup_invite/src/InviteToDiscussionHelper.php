<?php

declare (strict_types = 1);

namespace Drupal\joinup_invite;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\joinup_invite\EventSubscriber\InvitationSubscriber;
use Drupal\message\MessageInterface;
use Drupal\node\NodeInterface;

/**
 * Service containing methods for dealing with invitations to discussions.
 */
class InviteToDiscussionHelper {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an InviteToDiscussionHelper service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Returns the invitation message for the given discussion and user.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion for which to return the message.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user that was invited to participate in the discussion.
   *
   * @return \Drupal\message\MessageInterface|null
   *   The message, or NULL if the user wasn't invited to the discussion yet.
   */
  public function getInvitationMessage(NodeInterface $discussion, AccountInterface $user) : ?MessageInterface {
    $storage = $this->entityTypeManager->getStorage('message');
    $messages = $storage->loadByProperties([
      'template' => InvitationSubscriber::TEMPLATE_DISCUSSION_INVITE,
      'field_invitation_discussion' => $discussion->id(),
      'field_invitation_user' => $user->id(),
    ]);

    return $messages ? reset($messages) : NULL;
  }

  /**
   * Checks whether the given user has already been invited to the discussion.
   *
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion to check.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to check.
   *
   * @return bool
   *   TRUE if the user has already been invited to the discussion, FALSE
   *   otherwise.
   */
  public function invitationExists(NodeInterface $discussion, AccountInterface $user) : bool {
    return !empty($this->getInvitationMessage($discussion, $user));
  }

}

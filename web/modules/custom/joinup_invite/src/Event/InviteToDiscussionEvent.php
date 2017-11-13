<?php

declare(strict_types = 1);

namespace Drupal\joinup_invite\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that fires whenever a user is invited to participate in a discussion.
 */
class InviteToDiscussionEvent extends Event {

  /**
   * The value indicating a successful invitation.
   *
   * @var string
   */
  const RESULT_SUCCESS = 'success';

  /**
   * The value indicating a failed invitation.
   *
   * @var string
   */
  const RESULT_FAILED = 'failed';

  /**
   * The value indicating an invitation that has been resent.
   *
   * @var string
   */
  const RESULT_RESENT = 'resent';

  /**
   * The value indicating an invitation that has been previously accepted.
   *
   * @var string
   */
  const RESULT_ACCEPTED = 'accepted';

  /**
   * The value indicating an invitation that has been previously rejected.
   *
   * @var string
   */
  const RESULT_REJECTED = 'rejected';

  /**
   * The users who are invited to participate in the discussion.
   *
   * @var \Drupal\Core\Session\AccountInterface[]
   */
  protected $users;

  /**
   * The discussion.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $discussion;

  /**
   * The result of the event.
   *
   * @var array
   *   An associative array with the results of the invitations, with keys:
   *   - 'success': invitations that were successfully sent.
   *   - 'failed': invitations that could not be sent.
   *   - 'resent': invitations that were already sent and have been resent.
   *   - 'accepted': invitations that were not sent because the user is already
   *     subscribed to the discussion.
   *   - 'rejected': invitations that were not sent because the user has already
   *     previously rejected the invitation.
   *   Every value is an associative array with the following keys:
   *   - 'discussion': the discussion ID.
   *   - 'user': the user ID.
   */
  protected $result;

  /**
   * Constructs an InviteToDiscussionEvent.
   *
   * @param \Drupal\Core\Session\AccountInterface[] $users
   *   The users who are invited to participate in the discussion.
   * @param \Drupal\node\NodeInterface $discussion
   *   The discussion.
   */
  public function __construct(array $users, NodeInterface $discussion) {
    $this->users = $users;
    $this->discussion = $discussion;
  }

  /**
   * Returns the users who are invited to participate in the discussion.
   *
   * @return \Drupal\Core\Session\AccountInterface[]
   *   The users who are invited to participate in the discussion.
   */
  public function getUsers() : array {
    return $this->users;
  }

  /**
   * Returns the discussion entity.
   *
   * @return \Drupal\node\NodeInterface
   *   The discussion entity.
   */
  public function getDiscussion() : NodeInterface {
    return $this->discussion;
  }

  /**
   * @return array
   */
  public function getResult() : array {
    return $this->result;
  }

  /**
   * @param array $result
   */
  public function setResult(array $result) : void {
    $this->result = $result;
  }

}

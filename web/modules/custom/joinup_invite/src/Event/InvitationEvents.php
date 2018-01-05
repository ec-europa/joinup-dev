<?php

namespace Drupal\joinup_invite\Event;

/**
 * Events for the Joinup Invite module.
 */
final class InvitationEvents {

  /**
   * An event that fires when an invitation is accepted.
   *
   * @Event
   *
   * @var string
   */
  const ACCEPT_INVITATION_EVENT = 'joinup_invite.accept';

  /**
   * An event that fires when an invitation is accepted.
   *
   * @Event
   *
   * @var string
   */
  const REJECT_INVITATION_EVENT = 'joinup_invite.reject';

}

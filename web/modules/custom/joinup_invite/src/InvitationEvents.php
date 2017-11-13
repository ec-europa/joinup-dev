<?php

namespace Drupal\joinup_invite;

/**
 * Define events for the Joinup Invite module.
 */
final class InvitationEvents {

  /**
   * An event to be fired when a user is invited to participate in a discussion.
   *
   * @Event
   *
   * @var string
   */
  const INVITE_TO_DISCUSSION_EVENT = 'joinup_invite.discussion';

}

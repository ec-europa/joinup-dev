<?php

namespace Drupal\joinup_discussion;

/**
 * Define events for the Joinup Discussion module.
 */
final class DiscussionEvents {

  /**
   * A notification event that fires when a discussion is deleted.
   *
   * @Event
   *
   * @var string
   */
  const DISCUSSION_DELETED = 'joinup_discussion.discussion_deleted';

}

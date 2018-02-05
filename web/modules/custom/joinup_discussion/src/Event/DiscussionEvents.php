<?php

namespace Drupal\joinup_discussion\Event;

/**
 * Defines events for the Joinup Discussion module.
 */
final class DiscussionEvents {

  /**
   * The ID of an event that fires when a discussion is deleted.
   *
   * @Event
   *
   * @var string
   */
  const DELETE = 'joinup_discussion.discussion.delete';

  /**
   * The ID of an event that fires when a discussion is updated.
   *
   * @Event
   *
   * @var string
   */
  const UPDATE = 'joinup_discussion.discussion.update';

}

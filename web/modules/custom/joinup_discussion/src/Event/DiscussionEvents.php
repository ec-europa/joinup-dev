<?php

namespace Drupal\joinup_discussion\Event;

/**
 * Defines community content events.
 */
final class DiscussionEvents {

  /**
   * The ID of the event triggered when discussion relevant fields are changed.
   *
   * @Event
   *
   * @var string
   */
  const UPDATE = 'joinup_discussion.discussion.update';

}

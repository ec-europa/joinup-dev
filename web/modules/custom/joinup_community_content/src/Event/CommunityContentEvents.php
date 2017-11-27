<?php

namespace Drupal\joinup_community_content\Event;

/**
 * Defines community content events.
 */
final class CommunityContentEvents {

  /**
   * The ID of the event triggered when discussion relevant fields are changed.
   *
   * @Event
   *
   * @var string
   */
  const DISCUSSION_UPDATE = 'joinup.community_content.discussion.update';

}

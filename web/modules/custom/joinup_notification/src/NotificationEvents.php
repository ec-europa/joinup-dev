<?php

namespace Drupal\joinup_notification;

/**
 * Define events for the joinup notification module.
 */
final class NotificationEvents {

  /**
   * An event to be fired for crud operations that require a notification.
   *
   * @Event
   *
   * @var string
   */
  const COMMUNITY_CONTENT_CRUD = 'joinup_notification.notify';

}

<?php

declare(strict_types = 1);

namespace Drupal\joinup_notification;

/**
 * Define events for the joinup notification module.
 */
final class NotificationEvents {

  /**
   * An event for sending notifications on community content creation.
   *
   * @Event
   *
   * @var string
   */
  const COMMUNITY_CONTENT_CREATE = 'joinup_notification.cc.create';

  /**
   * An event for sending notifications on community content update.
   *
   * @Event
   *
   * @var string
   */
  const COMMUNITY_CONTENT_UPDATE = 'joinup_notification.cc.update';

  /**
   * An event for sending notifications on community content deletion.
   *
   * @Event
   *
   * @var string
   */
  const COMMUNITY_CONTENT_DELETE = 'joinup_notification.cc.delete';

  /**
   * An event that sends notifications on RDF entity CRUD operations.
   *
   * @Event
   *
   * @var string
   */
  const RDF_ENTITY_CRUD = 'joinup_notification.rdf.notify';

  /**
   * An event that sends notifications on comment CRUD operations.
   *
   * @Event
   *
   * @var string
   */
  const COMMENT_CRUD = 'joinup_notification.comment.notify';

  /**
   * An event that sends notifications when a membership state is changed.
   *
   * @Event
   *
   * @var string
   */
  const OG_MEMBERSHIP_MANAGEMENT = 'joinup_notification.og_membership.management';

}

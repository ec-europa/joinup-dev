<?php

namespace Drupal\joinup_notification;

/**
 * Define events for the joinup notification module.
 */
final class NotificationEvents {

  /**
   * An event that sends notifications on community content CRUD operations.
   *
   * @Event
   *
   * @var string
   */
  const COMMUNITY_CONTENT_CRUD = 'joinup_notification.cc.notify';

  /**
   * An event that sends notifications on RDF entity CRUD operations.
   *
   * @Event
   *
   * @var string
   */
  const RDF_ENTITY_CRUD = 'joinup_notification.rdf.notify';

}

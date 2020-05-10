<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge\EventSubscriber;

use Drupal\joinup_notification\EventSubscriber\CommunityContentSubscriber;

/**
 * Handles notifications related to community content.
 *
 * The basic configuration for the community content notifications cover this
 * case as well. We only need to provide a different notification scheme.
 */
class NotificationSubscriber extends CommunityContentSubscriber {

  /**
   * An event for sending notifications on a pledge create.
   *
   * @Event
   *
   * @var string
   */
  const PLEDGE_CREATE = 'joinup_notification.pledge.create';

  /**
   * An event for sending notifications on pledge update.
   *
   * @Event
   *
   * @var string
   */
  const PLEDGE_UPDATE = 'joinup_notification.pledge.update';

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      self::PLEDGE_CREATE => ['onCreate'],
      self::PLEDGE_UPDATE => ['onUpdate'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationName() {
    return 'easme_pledge.notifications.community_content';
  }

}

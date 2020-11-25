<?php

declare(strict_types = 1);

namespace Drupal\moderation\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for the Moderation module.
 */
class ModerationEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => 'provideOgPermission',
    ];
  }

  /**
   * Declare an OG permission for accessing the content moderation overview.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideOgPermission(PermissionEventInterface $event) {
    if ($event->getGroupEntityTypeId() === 'rdf_entity' && in_array($event->getGroupBundleId(), JoinupGroupHelper::GROUP_BUNDLES)) {
      $event->setPermission(new GroupPermission([
        'name' => 'access content moderation overview',
        'title' => $this->t('Access the content moderation overview'),
      ]));
    }
  }

}

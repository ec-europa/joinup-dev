<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class OgPermissionSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideWorkflowOgPermissions']],
    ];
  }

  /**
   * Provide a permission to allow authors bypass the group moderation flag.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideWorkflowOgPermissions(PermissionEventInterface $event) {
    $event->setPermission(
      new GroupPermission([
        'name' => 'publish content in moderated groups',
        'title' => $this->t('Publish community content in moderated groups.'),
        'default roles' => ['author'],
      ])
    );
  }

}

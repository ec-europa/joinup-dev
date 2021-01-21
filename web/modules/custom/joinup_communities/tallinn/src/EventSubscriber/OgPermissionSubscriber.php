<?php

declare(strict_types = 1);

namespace Drupal\tallinn\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Tallinn reports.
 */
class OgPermissionSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideTallinnOgPermissions']],
    ];
  }

  /**
   * Provide a permission for accessing the author field in Tallinn reports.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideTallinnOgPermissions(PermissionEventInterface $event) {
    $permission = new GroupPermission([
      'name' => 'change tallinn report author',
      'title' => $this->t('Change the author of a Tallinn report'),
      'description' => $this->t('Manage group members and content in the group.'),
      'default roles' => [],
      'restrict access' => TRUE,
    ]);

    $event->setPermission($permission);
  }

}

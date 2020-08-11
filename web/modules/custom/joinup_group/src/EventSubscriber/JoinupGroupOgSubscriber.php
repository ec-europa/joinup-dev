<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\GroupContentEntityOperationAccessEventInterface;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to events fired by Organic Groups.
 */
class JoinupGroupOgSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OgPermissionEventInterface::EVENT_NAME => [['provideOgGroupPermissions']],
      GroupContentEntityOperationAccessEventInterface::EVENT_NAME => [['moderatorsCanManageGroupContent']],
    ];
  }

  /**
   * Declare OG permissions for shared entities.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideOgGroupPermissions(OgPermissionEventInterface $event): void {
    $event->setPermission(
      new GroupPermission([
        'name' => 'administer shared entities',
        'title' => $this->t('Administer shared entities'),
        'restrict access' => TRUE,
      ])
    );
  }

  /**
   * Gives moderators access to view, create, edit and delete all group content.
   *
   * @param \Drupal\og\Event\GroupContentEntityOperationAccessEventInterface $event
   *   The event that fires when an entity operation is being performed on group
   *   content.
   */
  public function moderatorsCanManageGroupContent(GroupContentEntityOperationAccessEventInterface $event): void {
    $is_moderator = in_array('moderator', $event->getUser()->getRoles());
    $operation_allowed = in_array($event->getOperation(), [
      'view',
      'create',
      'update',
      'delete',
    ]);

    if ($is_moderator && $operation_allowed) {
      $event->grantAccess();
    }
  }

}

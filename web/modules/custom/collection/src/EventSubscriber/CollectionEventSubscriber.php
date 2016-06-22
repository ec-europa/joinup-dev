<?php

namespace Drupal\collection\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for the Collection module.
 */
class CollectionEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideDefaultOgPermissions']],
    ];
  }

  /**
   * Declare OG permissions for collections.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideDefaultOgPermissions(PermissionEventInterface $event) {
    if ($event->getGroupEntityTypeId() === 'rdf_entity' && $event->getGroupBundleId() === 'collection') {
      $event->setPermissions([
        new GroupPermission([
          'name' => 'request collection deletion',
          'title' => t('Request to delete collections'),
        ]),
        new GroupPermission([
          'name' => 'request collection archival',
          'title' => t('Request to archive collections'),
        ]),
        new GroupPermission([
          'name' => 'invite members',
          'title' => t('Invite users to become collection members'),
        ]),
        new GroupPermission([
          'name' => 'approve membership requests',
          'title' => t('Approve requests to join collections'),
        ]),
        new GroupPermission([
          'name' => 'invite facilitators',
          'title' => t('Invite users to become collection facilitators'),
        ]),
        new GroupPermission([
          'name' => 'accept facilitator invitation',
          'title' => t('Accept invitation to become collection facilitator'),
        ]),
        new GroupPermission([
          'name' => 'highlight collections',
          'title' => t('Highlight collections'),
        ]),
      ]);
    }
  }

}

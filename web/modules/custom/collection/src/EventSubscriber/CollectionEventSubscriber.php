<?php

namespace Drupal\collection\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
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
    if ($event->getEntityTypeId() === 'rdf_entity' && $event->getBundleId() === 'collection') {
      $event->setPermissions([
        'request collection deletion' => [
          'title' => t('Request to delete collections'),
        ],
        'request collection archival' => [
          'title' => t('Request to archive collections'),
        ],
        'invite members' => [
          'title' => t('Invite users to become collection members'),
        ],
        'approve membership requests' => [
          'title' => t('Approve requests to join collections'),
        ],
        'invite facilitators' => [
          'title' => t('Invite users to become collection facilitators'),
        ],
        'accept facilitator invitation' => [
          'title' => t('Accept invitation to become collection facilitator'),
        ],
        'highlight collections' => [
          'title' => t('Highlight collections'),
        ],
      ]);
    }
  }

}

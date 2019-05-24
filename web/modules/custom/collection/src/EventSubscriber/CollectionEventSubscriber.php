<?php

namespace Drupal\collection\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for the Collection module.
 */
class CollectionEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
          'title' => $this->t('Request to delete collections'),
        ]),
        new GroupPermission([
          'name' => 'request collection archival',
          'title' => $this->t('Request to archive collections'),
        ]),
        new GroupPermission([
          'name' => 'invite members',
          'title' => $this->t('Invite users to become collection members'),
        ]),
        new GroupPermission([
          'name' => 'approve membership requests',
          'title' => $this->t('Approve requests to join collections'),
        ]),
        new GroupPermission([
          'name' => 'invite facilitators',
          'title' => $this->t('Invite users to become collection facilitators'),
        ]),
        new GroupPermission([
          'name' => 'invite users to discussions',
          'title' => $this->t('Invite users to participate in discussions'),
        ]),
        new GroupPermission([
          'name' => 'accept facilitator invitation',
          'title' => $this->t('Accept invitation to become collection facilitator'),
        ]),
        new GroupPermission([
          'name' => 'highlight collections',
          'title' => $this->t('Highlight collections'),
        ]),
      ]);
    }
  }

}

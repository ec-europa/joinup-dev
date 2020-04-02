<?php

declare(strict_types = 1);

namespace Drupal\solution\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Solution subscriber for og related events.
 */
class SolutionOgSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OgPermissionEventInterface::EVENT_NAME => [['provideOgGroupPermissions']],
    ];
  }

  /**
   * Declare OG permissions for shared entities.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideOgGroupPermissions(OgPermissionEventInterface $event) {
    $event->setPermissions([
      new GroupContentOperationPermission([
        'name' => "unshare solution rdf_entity",
        'title' => $this->t('solution: Unshare from a group'),
        'operation' => 'unshare',
        'entity type' => 'rdf_entity',
        'bundle' => 'solution',
      ]),
      new GroupContentOperationPermission([
        'name' => "share solution rdf_entity",
        'title' => $this->t('solution: Unshare from a group'),
        'operation' => 'unshare',
        'entity type' => 'rdf_entity',
        'bundle' => 'solution',
      ]),
    ]);
  }

}

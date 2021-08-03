<?php

declare(strict_types = 1);

namespace Drupal\solution\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
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
      OgPermissionEventInterface::EVENT_NAME => [
        ['provideOgGroupPermissions'],
        ['provideEasmeGroupPermissions'],
      ],
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

  /**
   * Declare OG permissions for the private fields.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideEasmeGroupPermissions(OgPermissionEventInterface $event) {
    if ($event->getGroupEntityTypeId() === 'rdf_entity' && in_array($event->getGroupBundleId(), JoinupGroupHelper::GROUP_BUNDLES)) {
      $event->setPermissions([
        new GroupPermission([
          'name' => 'view solution private fields',
          'title' => $this->t('Allow users to view the solution private fields'),
        ]),
        new GroupPermission([
          'name' => 'edit solution private fields',
          'title' => $this->t('Allow users to edit the solution private fields'),
        ]),
      ]);
    }
  }

}

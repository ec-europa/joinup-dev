<?php

namespace Drupal\joinup_community_content\EventSubscriber;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for the Joinup community content module.
 */
class EventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The service providing information about bundles.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs an EventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OgPermissionEventInterface::EVENT_NAME => [['provideOgRevisionPermissions']],
    ];
  }

  /**
   * Declare OG permissions for handling revisions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideOgRevisionPermissions(OgPermissionEventInterface $event) {
    $group_content_bundle_ids = $event->getGroupContentBundleIds();

    if (!empty($group_content_bundle_ids['node'])) {
      // Add a global permission that allows to access all the revisions.
      $event->setPermissions([
        new GroupPermission([
          'name' => 'view all revisions',
          'title' => $this->t('View all revisions'),
          'restrict access' => TRUE,
        ]),
        new GroupPermission([
          'name' => 'revert all revisions',
          'title' => $this->t('Revert all revisions'),
          'restrict access' => TRUE,
        ]),
        new GroupPermission([
          'name' => 'delete all revisions',
          'title' => $this->t('Delete all revisions'),
          'restrict access' => TRUE,
        ]),
      ]);

      $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('node');
      foreach ($group_content_bundle_ids['node'] as $bundle_id) {
        $bundle_label = $bundle_info[$bundle_id]['label'];

        $event->setPermissions([
          new GroupContentOperationPermission([
            'name' => "view $bundle_id revisions",
            'title' => $this->t('%bundle: View revisions', ['%bundle' => $bundle_label]),
            'operation' => 'view revision',
            'entity type' => 'node',
            'bundle' => $bundle_id,
          ]),
          new GroupContentOperationPermission([
            'name' => "revert $bundle_id revisions",
            'title' => $this->t('%bundle: Revert revisions', ['%bundle' => $bundle_label]),
            'operation' => 'revert revision',
            'entity type' => 'node',
            'bundle' => $bundle_id,
          ]),
          new GroupContentOperationPermission([
            'name' => "delete $bundle_id revisions",
            'title' => $this->t('%bundle: Delete revisions', ['%bundle' => $bundle_label]),
            'operation' => 'delete revision',
            'entity type' => 'node',
            'bundle' => $bundle_id,
          ]),
        ]);
      }
    }
  }

}

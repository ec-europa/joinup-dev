<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;
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
   * The currently logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The service that determines the access to update workflow states.
   *
   * @var \Drupal\workflow_state_permission\WorkflowStatePermissionInterface
   */
  protected $workflowStatePermission;

  /**
   * Constructs an EventSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The service providing information about bundles.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflowStatePermission
   *   The service that determines the permission to update the workflow state
   *   of entities.
   */
  public function __construct(EntityTypeBundleInfoInterface $entity_type_bundle_info, AccountInterface $currentUser, WorkflowStatePermissionInterface $workflowStatePermission) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->currentUser = $currentUser;
    $this->workflowStatePermission = $workflowStatePermission;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OgPermissionEventInterface::EVENT_NAME => [['provideOgRevisionPermissions']],
      'joinup_workflow.unchanged_workflow_state_update' => 'onUnchangedWorkflowStateUpdate',
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
        new GroupPermission([
          'name' => 'administer shared entities',
          'title' => $this->t('Administer shared entities'),
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
          new GroupContentOperationPermission([
            'name' => "share $bundle_id content",
            'title' => $this->t('%bundle: Share onto a group', ['%bundle' => $bundle_label]),
            'operation' => 'share',
            'entity type' => 'node',
            'bundle' => $bundle_id,
          ]),
          new GroupContentOperationPermission([
            'name' => "unshare $bundle_id content",
            'title' => $this->t('%bundle: Unshare from a group', ['%bundle' => $bundle_label]),
            'operation' => 'unshare',
            'entity type' => 'node',
            'bundle' => $bundle_id,
          ]),
        ]);
      }
    }
  }

  /**
   * Determines if the content be updated without changing workflow state.
   *
   * @param \Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent $event
   *   The event.
   */
  public function onUnchangedWorkflowStateUpdate(UnchangedWorkflowStateUpdateEvent $event): void {
    $entity = $event->getEntity();
    if (!CommunityContentHelper::isCommunityContent($entity)) {
      return;
    }

    $state = $event->getState();
    $permitted = $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $event->getEntity(), $state, $state);
    $access = AccessResult::forbiddenIf(!$permitted);
    $access->addCacheContexts(['user.roles', 'og_role']);
    $event->setAccess($access);

    // Set a custom button label as defined in the functional specification.
    switch ($state) {
      case 'draft':
        $event->setLabel($this->t('Save as draft'));
        break;

      case 'proposed':
      case 'validated':
        $event->setLabel($this->t('Update'));
    }
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\collection\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for the Collection module.
 */
class CollectionEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
   * Constructs a CollectionEventSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\workflow_state_permission\WorkflowStatePermissionInterface $workflowStatePermission
   *   The service that determines the permission to update the workflow state
   *   of a given entity.
   */
  public function __construct(AccountInterface $currentUser, WorkflowStatePermissionInterface $workflowStatePermission) {
    $this->currentUser = $currentUser;
    $this->workflowStatePermission = $workflowStatePermission;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => 'provideDefaultOgPermissions',
      'joinup_workflow.unchanged_workflow_state_update' => 'onUnchangedWorkflowStateUpdate',
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

  /**
   * Determines if a collection can be updated without changing workflow state.
   *
   * This applies both to collections and solutions.
   *
   * @todo Move this in the 'joinup_group' module.
   *
   * @param \Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent $event
   *   The event.
   */
  public function onUnchangedWorkflowStateUpdate(UnchangedWorkflowStateUpdateEvent $event): void {
    $entity = $event->getEntity();
    if (!JoinupGroupHelper::isGroup($entity)) {
      return;
    }

    $state = $event->getState();
    $permitted = $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $event->getEntity(), $state, $state);
    $access = AccessResult::forbiddenIf(!$permitted);
    $access->addCacheContexts(['user.roles', 'og_role']);
    $event->setAccess($access);

    // If a published collection is updated, set the label to "Publish" and move
    // it to the start of the row of buttons.
    if ($state === 'validated') {
      $event->setLabel($this->t('Publish'));
      $event->setWeight(-20);
    }
  }

}

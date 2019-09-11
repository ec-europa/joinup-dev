<?php

declare(strict_types = 1);

namespace Drupal\collection\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_core\Event\UnchangedWorkflowStateUpdateEvent;
use Drupal\joinup_core\WorkflowStatePermissionInterface;
use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
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
   * @var \Drupal\joinup_core\WorkflowStatePermissionInterface
   */
  protected $collectionWorkflowStatePermission;

  /**
   * Constructs a CollectionEventSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   * @param \Drupal\joinup_core\WorkflowStatePermissionInterface $collectionWorkflowStatePermission
   *   The service that determines the permission to update the workflow state
   *   of a collection.
   */
  public function __construct(AccountInterface $currentUser, WorkflowStatePermissionInterface $collectionWorkflowStatePermission) {
    $this->currentUser = $currentUser;
    $this->collectionWorkflowStatePermission = $collectionWorkflowStatePermission;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => 'provideDefaultOgPermissions',
      UnchangedWorkflowStateUpdateEvent::EVENT_NAME => 'onUnchangedWorkflowStateUpdate',
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
   * @param \Drupal\joinup_core\Event\UnchangedWorkflowStateUpdateEvent $event
   *   The event.
   */
  public function onUnchangedWorkflowStateUpdate(UnchangedWorkflowStateUpdateEvent $event): void {
    $entity = $event->getEntity();
    if ($entity->getEntityTypeId() !== 'rdf_entity' || $entity->bundle() !== 'collection') {
      return;
    }

    $state = $event->getState();
    $permitted = $this->collectionWorkflowStatePermission->isStateUpdatePermitted($this->currentUser, $event->getEntity(), $state, $state);
    $access = AccessResult::forbiddenIf(!$permitted);
    $access->addCacheContexts(['user.roles', 'og_role']);
    $event->setAccess($access);

    // If a published collection is updated, set the label to "Publish" and move
    // it to the end of the row of buttons.
    if ($state === 'validated') {
      $event->setLabel($this->t('Publish'));
      $event->setWeight(0);
    }
  }

}

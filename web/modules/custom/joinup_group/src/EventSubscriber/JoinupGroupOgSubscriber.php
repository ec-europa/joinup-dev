<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\workflow_state_permission\WorkflowStatePermissionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to events fired by Organic Groups.
 */
class JoinupGroupOgSubscriber implements EventSubscriberInterface {

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
   * Constructs a JoinupGroupOgSubscriber.
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
      OgPermissionEventInterface::EVENT_NAME => [['provideOgGroupPermissions']],
      'joinup_workflow.unchanged_workflow_state_update' => 'onUnchangedWorkflowStateUpdate',
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
   * Determines if a group can be updated without changing workflow state.
   *
   * This applies both to collections and solutions.
   *
   * @param \Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent $event
   *   The event.
   */
  public function onUnchangedWorkflowStateUpdate(UnchangedWorkflowStateUpdateEvent $event): void {
    $entity = $event->getEntity();
    if (!$entity instanceof GroupInterface || !$entity instanceof EntityWorkflowStateInterface) {
      return;
    }

    $workflow = $entity->getWorkflow();
    $state = $event->getState();
    $permitted = $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $event->getEntity(), $workflow, $state, $state);
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

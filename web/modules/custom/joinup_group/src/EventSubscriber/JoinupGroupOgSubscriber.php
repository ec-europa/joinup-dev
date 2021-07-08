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
use Drupal\og\GroupContentOperationPermission;
use Drupal\og\GroupPermission;
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
   * Constructs a JoinupGroupOgSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current logged in user.
   */
  public function __construct(AccountInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OgPermissionEventInterface::EVENT_NAME => [
        ['provideOgGroupPermissions'],
        ['checkWorkflowGroupPermissions'],
      ],
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
    $event->setPermissions([
      new GroupPermission([
        'name' => 'access group reports',
        'title' => $this->t('Access the group reports page.'),
        'restrict access' => TRUE,
      ]),
      new GroupPermission([
        'name' => 'administer shared entities',
        'title' => $this->t('Administer shared entities'),
        'restrict access' => TRUE,
      ]),
    ]);
  }

  /**
   * Determines if a group can be updated without changing workflow state.
   *
   * This applies both to collections and solutions. It also updates the label
   * for the button that saves a group without changing state and moves it to
   * the leftmost position.
   *
   * @param \Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent $event
   *   The event.
   */
  public function onUnchangedWorkflowStateUpdate(UnchangedWorkflowStateUpdateEvent $event): void {
    $entity = $event->getEntity();
    if (!$entity instanceof GroupInterface || !$entity instanceof EntityWorkflowStateInterface) {
      return;
    }

    $state = $event->getState();
    $permitted = $entity->isTargetWorkflowStateAllowed($state, $state);
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

  /**
   * Check OG permissions for content.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function checkWorkflowGroupPermissions(OgPermissionEventInterface $event): void {
    $permissions = $event->getPermissions();
    foreach ($permissions as $permission) {
      if ($permission instanceof GroupContentOperationPermission) {
        $event->deletePermission($permission->getName());
      }
    }
  }

}

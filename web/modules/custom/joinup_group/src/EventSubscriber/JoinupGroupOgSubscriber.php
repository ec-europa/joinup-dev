<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\Entity\GroupInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent;
use Drupal\og\Event\GroupContentEntityOperationAccessEventInterface;
use Drupal\og\Event\PermissionEventInterface as OgPermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgMembershipInterface;
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
      GroupContentEntityOperationAccessEventInterface::EVENT_NAME => [
        ['preventBlockedUsersFromEditingOrDeletingContent'],
      ],
      OgPermissionEventInterface::EVENT_NAME => [
        ['provideOgGroupPermissions'],
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
   * Prevents blocked users from editing or deleting their content.
   *
   * @param \Drupal\og\Event\GroupContentEntityOperationAccessEventInterface $event
   *   The event fired when a group content entity operation is performed.
   */
  public function preventBlockedUsersFromEditingOrDeletingContent(GroupContentEntityOperationAccessEventInterface $event): void {
    // Blocked users should not be able to edit or delete content in groups.
    // The main use case for this is to prevent vandalism when a member is
    // removed or blocked.
    if (in_array($event->getOperation(), ['update', 'delete'])) {
      $user = $event->getUser();
      /** @var \Drupal\joinup_group\Entity\GroupInterface $group */
      $group = $event->getGroup();
      $membership = $group->getMembership((int) $user->id(), OgMembershipInterface::ALL_STATES);
      if ($membership && $membership->isBlocked()) {
        $event->denyAccess();
      }
    }
  }

}

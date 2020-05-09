<?php

declare(strict_types = 1);

namespace Drupal\easme_pledge\EventSubscriber;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent;
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
      'joinup_workflow.unchanged_workflow_state_update' => 'onUnchangedWorkflowStateUpdate',
    ];
  }

  /**
   * Determines if the content be updated without changing workflow state.
   *
   * @param \Drupal\joinup_workflow\Event\UnchangedWorkflowStateUpdateEvent $event
   *   The event.
   */
  public function onUnchangedWorkflowStateUpdate(UnchangedWorkflowStateUpdateEvent $event): void {
    $entity = $event->getEntity();
    if ($entity->bundle() !== 'pledge') {
      return;
    }

    $state = $event->getState();
    $permitted = $this->workflowStatePermission->isStateUpdatePermitted($this->currentUser, $event->getEntity(), $state, $state);
    $access = AccessResult::forbiddenIf(!$permitted);
    $access->addCacheContexts(['user.roles']);
    $event->setAccess($access);

    // Set a custom button label as defined in the functional specification.
    switch ($state) {
      case 'draft':
        $event->setLabel($this->t('Save as draft'));
        break;

      default:
        $event->setLabel($this->t('Update'));
    }
  }

}

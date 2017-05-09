<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\joinup_core\WorkflowHelperInterface;
use Drupal\joinup_notification\NotificationSenderService;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The event subscriber that handles the message notifications in joinup.
 *
 * @package Drupal\joinup_notification
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The notification sender service.
   *
   * @var \Drupal\joinup_notification\NotificationSenderService
   */
  protected $notificationSender;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The workflow helper service.
   *
   * @var \Drupal\joinup_core\WorkflowHelperInterface
   */
  protected $workflowHelper;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\joinup_notification\NotificationSenderService $notification_sender
   *   The message notify sender service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\joinup_core\WorkflowHelperInterface $workflow_helper
   *   The workflow helper service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, NotificationSenderService $notification_sender, ConfigFactoryInterface $config_factory, WorkflowHelperInterface $workflow_helper) {
    $this->entityFieldManager = $entity_field_manager;
    $this->notificationSender = $notification_sender;
    $this->configFactory = $config_factory;
    $this->workflowHelper = $workflow_helper;
  }

  /**
   * Handler method for the message notifications.
   *
   * All notifications are stored in the configuration files of the module.
   * This method only handles the transition notifications. These notifications
   * include the create and update operations on the entity. All notifications
   * are sent in the post transition event.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The state change event.
   *
   * @throws \Drupal\message_notify\Exception\MessageNotifyException
   *
   * @see modules/custom/joinup_notification/src/config/schema/joinup_notification.schema.yml
   */
  public function messageSender(WorkflowTransitionEvent $event) {
    $configuration = $this->configFactory->get('joinup_notification.settings')->get('transition_notifications');
    $entity = $event->getEntity();
    $workflow = $this->workflowHelper->getEntityStateField($entity)->getWorkflow();
    $transition = $workflow->findTransition($event->getFromState()->getId(), $event->getToState()->getId());

    foreach ($configuration[$workflow->getGroup()][$transition->getId()] as $role_id => $messages) {
      $this->notificationSender->sendStateTransitionMessage($entity, $role_id, $messages);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $keys = [
      'solution.validate.post_transition',
      'solution.request_deletion.post_transition',
      'asset_release.validate.post_transition',
      'asset_release.update_published.post_transition',
    ];

    foreach ($keys as $key) {
      $events[$key][] = ['messageSender'];
    }

    return $events;
  }

}

<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\message_notify\MessageNotifier;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\og\OgRoleManager;

/**
 * Class WorkflowTransitionEventSubscriber.
 *
 * @package Drupal\joinup_notification
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\message_notify\MessageNotifier definition.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\og\OgRoleManager definition.
   *
   * @var \Drupal\og\OgRoleManager
   */
  protected $ogRoleManager;

  /**
   * Constructor.
   */
  public function __construct(MessageNotifier $message_notify_sender, EntityTypeManager $entity_type_manager, OgRoleManager $og_role_manager) {
    $this->messageNotifySender = $message_notify_sender;
    $this->entityTypeManager = $entity_type_manager;
    $this->ogRoleManager = $og_role_manager;
  }

  /**
   * On workflow transition.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The state change event.
   *
   * @throws \Drupal\message_notify\Exception\MessageNotifyException
   */
  public function messageSender(WorkflowTransitionEvent $event) {
    $configuration = \Drupal::config('joinup_notification.settings')->get('notifications');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $configuration = \Drupal::config('joinup_notification.settings')->get('notifications');
    $events = [];
    foreach ($configuration as $entity_type => $workflow_groups) {
      foreach ($workflow_groups as $workflow_group => $transitions) {
        foreach ($transitions as $transition => $roles) {
          $event_name = $workflow_group . '.' . $transition . '.post_transition';
          $events[$event_name][] = ['messageSender'];
        }
      }
    }

    return $events;
  }

}

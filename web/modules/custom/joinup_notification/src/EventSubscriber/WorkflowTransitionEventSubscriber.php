<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\message\Entity\Message;
use Drupal\og\Entity\OgMembership;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface;
use Drupal\user\Entity\Role;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\message_notify\MessageNotifier;

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
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructor.
   */
  public function __construct(MessageNotifier $message_notify_sender, EntityManager $entity_manager) {
    $this->messageNotifySender = $message_notify_sender;
    $this->entityManager = $entity_manager;
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

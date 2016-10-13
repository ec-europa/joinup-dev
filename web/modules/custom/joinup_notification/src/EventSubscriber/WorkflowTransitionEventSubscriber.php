<?php

namespace Drupal\joinup_notification\EventSubscriber;

use Drupal\Core\Entity\EntityManager;
use Drupal\message\Entity\Message;
use Drupal\og\Entity\OgMembership;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\user\Entity\Role;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\message_notify\MessageNotifier;

/**
 * The event subscriber that handles the message notifications in joinup.
 *
 * @package Drupal\joinup_notification
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * The message notify sender service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifySender;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs the event object.
   *
   * @param \Drupal\message_notify\MessageNotifier $message_notify_sender
   *    The message notify sender service.
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *    The entity manager service.
   */
  public function __construct(MessageNotifier $message_notify_sender, EntityManager $entity_manager) {
    $this->messageNotifySender = $message_notify_sender;
    $this->entityManager = $entity_manager;
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
   * @see \Drupal\joinup_notification\config\schema\joinup_notification.schema.yml
   */
  public function messageSender(WorkflowTransitionEvent $event) {
    $configuration = \Drupal::config('joinup_notification.settings')->get('transition_notifications');
    $entity = $event->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $field_definitions = array_filter($this->entityManager->getFieldDefinitions($entity_type, $bundle), function ($field_definition) {
      return $field_definition->getType() == 'state';
    });

    $field_definition = array_pop($field_definitions);
    $state_field = $entity->{$field_definition->getName()}->first();
    $workflow = $state_field->getWorkflow();
    $transition = $workflow->findTransition($event->getFromState()->getId(), $event->getToState()->getId());

    foreach ($configuration[$workflow->getGroup()][$transition->getId()] as $role_id => $messages) {
      // The notifications might be sent to users having site wide roles or
      // users having og roles.
      if ($role = Role::load($role_id)) {
        $user_ids = $this->entityManager->getStorage('user')->getQuery()
          ->condition('user_role', $role_id)
          ->execute();
        $recipients = $user_ids;
      }
      else {
        $membership_query = $this->entityManager->getStorage('og_membership')->getQuery()
          ->condition('state', 'active')
          ->condition('entity_id', $entity->id());
        $memberships_ids = $membership_query->execute();
        $memberships = OgMembership::loadMultiple($memberships_ids);
        $memberships = array_filter($memberships, function ($membership) use ($role_id) {
          $roles = $membership->getRoles();
          $role_ids = array_keys($roles);
          return in_array($role_id, $role_ids);
        });
        $recipients = array_map(function ($membership) {
          return $membership->getUser()->id();
        }, $memberships);
      }

      // Send all the appropriate messages to their corresponding recipients.
      foreach ($recipients as $user_id) {
        foreach ($messages as $message_id) {
          // Create the actual message and save it to the db.
          $message = Message::create([
            'template' => $message_id,
            'uid' => $user_id,
            'field_message_content' => $entity->id(),
          ]);
          $message->save();
          // Send the saved message as an e-mail.
          $this->messageNotifySender->send($message, [], 'email');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $keys = [
      'solution.validate.post_transition',
    ];

    foreach ($keys as $key) {
      $events[$key][] = ['messageSender'];
    }

    return $events;
  }

}

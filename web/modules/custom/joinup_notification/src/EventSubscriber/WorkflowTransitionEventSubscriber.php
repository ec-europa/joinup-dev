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
    $entity = $event->getEntity();
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $field_definitions = array_filter($this->entityManager->getFieldDefinitions($entity_type, $bundle), function($field_definition) {
      return $field_definition->getType() == 'state';
    });

    /** @var FieldDefinitionInterface $field_definition */
    $field_definition = array_pop($field_definitions);
    /** @var StateItemInterface $state_field */
    $state_field = $entity->{$field_definition->getName()}->first();
    $workflow = $state_field->getWorkflow();
    $transition = $workflow->findTransition($event->getFromState(), $event->getToState());


    foreach ($configuration[$workflow->getGroup()][$transition->getId()] as $role_id => $messages) {
      $role = Role::load($role_id);
      if (!empty($role)) {
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
        $recipients = array_map(function($membership) {
          return $membership->getUser()->id();
        }, $memberships);
      }

      /** @var OgMembership $membership */
      foreach ($recipients as $user_id) {
        foreach ($messages as $message_id){
          // Create the actual message and save it to the db.
          $message = Message::create([
            'template' => $message_id,
            'uid' => $user_id,
            'field_message_content' => $entity->id(),
          ]);
          $message->save();
          // Send the saved message as an e-mail.
          $this->messageNotifier->send($message, [], 'email');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $configuration = \Drupal::config('joinup_notification.settings')->get('notifications');
    $events = [];
    foreach ($configuration as $workflow_group => $transitions) {
      foreach ($transitions as $transition => $roles) {
        $event_name = $workflow_group . '.' . $transition . '.post_transition';
        $events[$event_name][] = ['messageSender'];
      }
    }

    return $events;
  }

}

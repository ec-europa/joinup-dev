<?php

namespace Drupal\solution\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageTemplate;
use Drupal\message_notify\MessageNotifier;
use Drupal\og\Entity\OgMembership;
use Drupal\state_machine\Event\StateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * The message notifier service.
   *
   * @var \Drupal\message_notify\MessageNotifier
   */
  protected $messageNotifier;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a subscriber object. Passes services to the class.
   *
   * @param \Drupal\message_notify\MessageNotifier $message_notifier
   *    The message notifier service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *    The entity type manager service.
   */
  public function __construct(MessageNotifier $message_notifier, EntityTypeManager $entity_type_manager) {
    $this->messageNotifier = $message_notifier;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * On workflow transition.
   *
   * @param \Drupal\state_machine\Event\StateChangeEvent $event
   *   The state change event.
   *
   * @throws \Drupal\message_notify\Exception\MessageNotifyException
   */
  public function onChangeToValidated(StateChangeEvent $event) {
    $entity = $event->getEntity();
    $bundle = $entity->bundle();
    if ($bundle != 'solution') {
      return;
    }
    $message_template = MessageTemplate::load('workflow_transition');
    $storage = $this->entityTypeManager->getStorage('og_membership');

    // Sent a message to all solution administrators.
    $membership_query = $storage->getQuery()
      ->condition('state', 'active')
      ->condition('entity_id', $entity->id());
    $memberships_ids = $membership_query->execute();
    $memberships = OgMembership::loadMultiple($memberships_ids);
    $memberships = array_filter($memberships, function ($membership) {
      return $membership->hasPermission('message notification on validate');
    });

    /** @var OgMembership $membership */
    foreach ($memberships as $membership) {
      $uid = $membership->get('uid')->first()->getValue()['target_id'];
      // Create the actual message and save it to the db.
      $message = Message::create([
        'template' => $message_template->id(),
        'uid' => $uid,
        'field_message_content' => $entity->id(),
      ]);
      $message->save();
      // Send the saved message as an e-mail.
      $this->messageNotifier->send($message, [], 'email');
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['state_machine.state.validated'][] = array('onChangeToValidated');
    return $events;
  }

}

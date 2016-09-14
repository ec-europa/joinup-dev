<?php

namespace Drupal\solution\EventSubscriber;

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
   * Constructs an WorkflowTransitionEventSubscriber object.
   */
  public function __construct(MessageNotifier $messageNotifier) {
    $this->messageNotifier = $messageNotifier;
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

    $storage = \Drupal::entityManager()->getStorage('og_membership');

    // Sent a message to all solution administrators.
    $membership_query = $storage->getQuery();
    $membership_query
      ->condition('state', 'active')
      ->condition('entity_id', $entity->id())
      ->condition('roles', 'rdf_entity-solution-administrator');
    $memberships_ids = $membership_query->execute();
    $memberships = OgMembership::loadMultiple($memberships_ids);
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

<?php

namespace Drupal\solution\EventSubscriber;

use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageTemplate;
use Drupal\message_notify\MessageNotifier;
use Drupal\state_machine\Event\StateChangeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for Organic Groups.
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /** @var \Drupal\message_notify\MessageNotifier MessageNotifier */
  protected $messageNotifier;

  /**
   * Constructs an WorkflowTransitionEventSubscriber object.
   */
  public function __construct(MessageNotifier $messageNotifier) {
    $this->messageNotifier = $messageNotifier;
  }

  public function onChangeToValidated(StateChangeEvent $event) {
    $state = $event->getState();
    $entity = $event->getEntity();
    // @todo Check type!
    $message_template = MessageTemplate::load('workflow_transition');
    $message = Message::create([
        'template' => $message_template->id(),
        'uid' => 1,
        'field_message_content' => $entity->id(),
      ]
    );
    $message->save();
    $this->messageNotifier->send($message, [], 'email');
  }


  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['state_machine.state.validated'][] = array('onChangeToValidated');
    return $events;
  }

}

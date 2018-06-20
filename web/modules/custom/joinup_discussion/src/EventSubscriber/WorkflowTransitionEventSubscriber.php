<?php

namespace Drupal\joinup_discussion\EventSubscriber;

use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to close comments on archived discussions.
 */
class WorkflowTransitionEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'discussion.disable.pre_transition' => 'closeComments',
    ];
  }

  /**
   * Close discussion comments when a discussion is archived.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The state change event.
   */
  public function closeComments(WorkflowTransitionEvent $event) {
    $event->getEntity()->get('field_replies')->status = CommentItemInterface::CLOSED;
  }

}

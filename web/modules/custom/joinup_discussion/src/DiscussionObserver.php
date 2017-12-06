<?php

namespace Drupal\joinup_discussion;

use Drupal\changed_fields\ObserverInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\joinup_discussion\Event\DiscussionEvent;
use Drupal\joinup_discussion\Event\DiscussionEvents;

/**
 * Defines an observer for discussion node changes.
 */
class DiscussionObserver implements ObserverInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Builds a new discussion observer.
   *
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(ContainerAwareEventDispatcher $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      'discussion' => [
        'title',
        'body',
        'field_policy_domain',
        'field_keywords',
        'field_attachment',
        'status',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function update(\SplSubject $node_subject) {
    /** @var \Drupal\changed_fields\NodeSubject $node_subject */
    if ($changed_fields = $node_subject->getChangedFields()) {
      $node = $node_subject->getNode();
      $event = new DiscussionEvent($node, $changed_fields);
      $this->eventDispatcher->dispatch(DiscussionEvents::UPDATE, $event);
    }
  }

}

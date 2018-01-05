<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion;

use Drupal\changed_fields\ObserverInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\joinup_discussion\Event\DiscussionEvents;
use Drupal\joinup_discussion\Event\DiscussionUpdateEvent;

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
  public function getInfo(): array {
    return [
      'discussion' => [
        'title',
        'body',
        'field_policy_domain',
        'field_keywords',
        'field_attachment',
        'field_state',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function update(\SplSubject $node_subject): void {
    /** @var \Drupal\changed_fields\NodeSubject $node_subject */
    $discussion = $node_subject->getNode();
    $changed_fields = $node_subject->getChangedFields();
    // Dispatch the update event only if there are changes of relevant fields
    // and the discussion is in the 'validated' state.
    if ($changed_fields && $discussion->get('field_state')->value === 'validated') {
      $event = new DiscussionUpdateEvent($discussion, $changed_fields);
      $this->eventDispatcher->dispatch(DiscussionEvents::UPDATE, $event);
    }
  }

}

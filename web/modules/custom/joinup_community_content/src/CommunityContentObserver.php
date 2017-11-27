<?php

namespace Drupal\joinup_community_content;

use Drupal\changed_fields\ObserverInterface;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\joinup_community_content\Event\CommunityContentEvent;
use Drupal\joinup_community_content\Event\CommunityContentEvents;

/**
 * Defines an observer for community content node changes.
 */
class CommunityContentObserver implements ObserverInterface {

  /**
   * The event dispatcher service.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Builds a new community content observer.
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
      switch ($node->bundle()) {
        case 'discussion':
          if (!$node->isNew()) {
            $event_id = CommunityContentEvents::DISCUSSION_UPDATE;
          }
          break;
      }

      // Dispatch the event.
      if (isset($event_id)) {
        $event = new CommunityContentEvent($node, $changed_fields);
        $this->eventDispatcher->dispatch($event_id, $event);
      }
    }
  }

}

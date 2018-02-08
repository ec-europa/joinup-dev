<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A base class for discussion events.
 */
abstract class DiscussionEventBase extends Event {

  /**
   * The discussion node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Creates a new discussion event object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The discussion node subject of event.
   */
  public function __construct(NodeInterface $node) {
    $this->node = $node;
  }

  /**
   * Returns the discussion node.
   *
   * @return \Drupal\node\NodeInterface
   *   The discussion node.
   */
  public function getNode(): NodeInterface {
    return $this->node;
  }

}

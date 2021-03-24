<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Event;

use Drupal\joinup_discussion\Entity\DiscussionInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * A base class for discussion events.
 */
abstract class DiscussionEventBase extends Event {

  /**
   * The discussion node.
   *
   * @var \Drupal\joinup_discussion\Entity\DiscussionInterface
   */
  protected $node;

  /**
   * Creates a new discussion event object.
   *
   * @param \Drupal\joinup_discussion\Entity\DiscussionInterface $node
   *   The discussion node subject of event.
   */
  public function __construct(DiscussionInterface $node) {
    $this->node = $node;
  }

  /**
   * Returns the discussion node.
   *
   * @return \Drupal\joinup_discussion\Entity\DiscussionInterface
   *   The discussion node.
   */
  public function getNode(): DiscussionInterface {
    return $this->node;
  }

}

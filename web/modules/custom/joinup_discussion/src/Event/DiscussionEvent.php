<?php

namespace Drupal\joinup_discussion\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines a discussion event.
 */
class DiscussionEvent extends Event {

  /**
   * The discussion node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * A list of changed fields.
   *
   * @var array
   */
  protected $changedFields;

  /**
   * Creates a new discussion event object.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The discussion node subject of event.
   * @param array $changed_fields
   *   A list of changed fields, keyed by field name.
   */
  public function __construct(NodeInterface $node, array $changed_fields) {
    $this->node = $node;
    $this->changedFields = $changed_fields;
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

  /**
   * Returns the list of changed fields.
   *
   * @return array
   *   The list of changed fields, keyed by field name.
   */
  public function getChangedFields() {
    return $this->changedFields;
  }

}

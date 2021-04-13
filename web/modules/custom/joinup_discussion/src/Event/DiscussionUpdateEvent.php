<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Event;

use Drupal\joinup_discussion\Entity\DiscussionInterface;

/**
 * An event to fire whenever a discussion is updated.
 */
class DiscussionUpdateEvent extends DiscussionEventBase {

  /**
   * A list of changed fields.
   *
   * @var array
   */
  protected $changedFields;

  /**
   * Creates a new discussion event object.
   *
   * @param \Drupal\joinup_discussion\Entity\DiscussionInterface $node
   *   The discussion node subject of event.
   * @param array $changed_fields
   *   A list of changed fields, keyed by field name.
   */
  public function __construct(DiscussionInterface $node, array $changed_fields) {
    parent::__construct($node);
    $this->changedFields = $changed_fields;
  }

  /**
   * Returns the list of changed fields.
   *
   * @return array
   *   The list of changed fields, keyed by field name.
   */
  public function getChangedFields(): array {
    return $this->changedFields;
  }

}

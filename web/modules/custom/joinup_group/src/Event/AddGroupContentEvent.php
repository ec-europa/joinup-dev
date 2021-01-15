<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Event;

use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event class to react when building the group add content block.
 */
class AddGroupContentEvent extends Event {

  /**
   * The ID of the event when building the group content adding block.
   *
   * @var string
   */
  public const BUILD_BLOCK = 'joinup_group.build_add_content_block';

  /**
   * The group RDF entity.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $group;

  /**
   * A list of render arrays, each representing a menu item.
   *
   * @var array[]
   */
  protected $items = [];

  /**
   * Constructs a new event instance.
   *
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group entity.
   */
  public function __construct(RdfInterface $group) {
    $this->group = $group;
  }

  /**
   * Returns the group.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *   The group entity.
   */
  public function getGroup(): RdfInterface {
    return $this->group;
  }

  /**
   * Returns the list if items.
   *
   * @return array[]
   *   A list of render arrays.
   */
  public function getItems(): array {
    return $this->items;
  }

  /**
   * Adds a new item to the block menu.
   *
   * @param array $item
   *   A render array.
   *
   * @return $this
   */
  public function addItem(array $item): self {
    $this->items[] = $item;
    return $this;
  }

}

<?php

namespace Drupal\sparql_entity_storage\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * An event dispatched when the default graph IDs list is built.
 */
class DefaultGraphsEvent extends Event {

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The list of default graph IDs.
   *
   * @var array
   */
  protected $defaultGraphIds = [];

  /**
   * Instantiates a new event object.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $default_graph_ids
   *   A list of graph IDs.
   */
  public function __construct(string $entity_type_id, array $default_graph_ids) {
    $this->entityTypeId = $entity_type_id;
    $this->defaultGraphIds = $default_graph_ids;
  }

  /**
   * Sets the list of default graph IDs.
   *
   * @param array $default_graph_ids
   *   A list of graph IDs.
   *
   * @return $this
   */
  public function setDefaultGraphIds(array $default_graph_ids): self {
    $this->defaultGraphIds = $default_graph_ids;
    return $this;
  }

  /**
   * Returns the list of default graph IDs.
   *
   * @return string[]
   *   A list of default graph IDs.
   */
  public function getDefaultGraphIds(): array {
    return $this->defaultGraphIds;
  }

}

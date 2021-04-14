<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event to determine the graph to use to load an entity.
 */
class ActiveGraphEvent extends Event {

  /**
   * The SPARQL entity ID.
   *
   * @var string
   */
  protected $entityId;

  /**
   * The parameter definition provided in the route options.
   *
   * @var mixed
   */
  protected $parameterDefinition;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The route parameter name.
   *
   * @var string
   */
  protected $parameterName;

  /**
   * The route defaults.
   *
   * @var array
   */
  protected $routeDefaults;

  /**
   * A list of graph IDs.
   *
   * @var string[]
   */
  protected $graphs = NULL;

  /**
   * Construct the event that determines the graph used to load the entity from.
   *
   * @param string $parameter_name
   *   The name of the parameter.
   * @param string $entity_id
   *   The raw value.
   * @param string $entity_type_id
   *   The entity type ID.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param array $route_defaults
   *   The route defaults array.
   */
  public function __construct(string $parameter_name, string $entity_id, string $entity_type_id, $definition, array $route_defaults) {
    $this->parameterName = $parameter_name;
    $this->entityId = $entity_id;
    $this->entityTypeId = $entity_type_id;
    $this->parameterDefinition = $definition;
    $this->routeDefaults = $route_defaults;
  }

  /**
   * The SPARQL entity value.
   *
   * @return string
   *   The SPARQL entity value.
   */
  public function getEntityId(): string {
    return $this->entityId;
  }

  /**
   * Returns the entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  public function getEntityTypeId(): string {
    return $this->entityTypeId;
  }

  /**
   * The parameter definition provided in the route options.
   *
   * @return mixed
   *   The parameter definition provided in the route options.
   */
  public function getParameterDefinition() {
    return $this->parameterDefinition;
  }

  /**
   * The name of the route parameter.
   *
   * @return string
   *   The parameter name.
   */
  public function getParameterName(): string {
    return $this->parameterName;
  }

  /**
   * The route defaults array.
   *
   * @return array
   *   The route defaults.
   */
  public function getRouteDefaults(): array {
    return $this->routeDefaults;
  }

  /**
   * Gets the list of graphs.
   *
   * @return string[]|null
   *   A list of graph IDs or NULL if none.
   */
  public function getGraphs(): ?array {
    return $this->graphs;
  }

  /**
   * The graphs used to load the entity.
   *
   * @param string[] $graphs
   *   The graphs used to load the entity.
   *
   * @return $this
   */
  public function setGraphs(array $graphs): self {
    $this->graphs = $graphs;
    return $this;
  }

}

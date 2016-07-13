<?php

namespace Drupal\rdf_entity;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event to determine the graph to use to load an entity.
 */
class ActiveGraphEvent extends Event {
  protected $value;
  protected $definition;
  protected $converterName;
  protected $defaults;
  protected $graph;

  /**
   * Construct the event that determines the graph used to load the entity from.
   *
   * @param string $value
   *   The raw value.
   * @param mixed $definition
   *   The parameter definition provided in the route options.
   * @param string $name
   *   The name of the parameter.
   * @param array $defaults
   *   The route defaults array.
   */
  public function __construct($value, $definition, $name, array $defaults) {
    $this->value = $value;
    $this->definition = $definition;
    $this->converterName = $name;
    $this->defaults = $defaults;
  }

  /**
   * Getter: The raw value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Getter: The parameter definition provided in the route options.
   */
  public function getDefinition() {
    return $this->definition;
  }

  /**
   * Getter: The name of the parameter.
   */
  public function getConverterName() {
    return $this->converterName;
  }

  /**
   * Getter: The route defaults array.
   */
  public function getDefaults() {
    return $this->defaults;
  }

  /**
   * Getter: The active graph.
   */
  public function getGraph() {
    return $this->graph;
  }

  /**
   * Setter: The graph used to load the entity.
   */
  public function setGraph($graph) {
    $this->graph = $graph;
  }

}

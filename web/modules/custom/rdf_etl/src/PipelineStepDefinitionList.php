<?php

namespace Drupal\rdf_etl;

/**
 * Class PipelineStepDefinitionList.
 *
 * @package Drupal\rdf_etl
 */
class PipelineStepDefinitionList implements \Iterator {
  protected $list = [];
  protected $position = 0;

  /**
   * Adds a new step to the pipeline.
   *
   * @param string $plugin_id
   *   The plugin id of the data plugin to add to the pipeline.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinition
   *   The pipeline step definition.
   */
  public function add(string $plugin_id) : PipelineStepDefinition {
    $this->list[] = new PipelineStepDefinition($plugin_id);
    $this->position = key($this->list);
    return $this->list[$this->position];
  }

  /**
   * Fetches the first item from the list.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinition
   *   The pipeline step definition.
   */
  public function first() : PipelineStepDefinition {
    return $this->list[0];
  }

  /**
   * {@inheritdoc}
   */
  public function current() : PipelineStepDefinition {
    if (!$this->valid()) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$this->position];
  }

  /**
   * Returns a specified item from the list.
   *
   * @param int $position
   *   The index.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinition
   *   The pipeline step definition.
   *
   * @throws \Exception
   */
  public function get($position) : PipelineStepDefinition {
    if (!isset($this->list[$position])) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$position];
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return isset($this->list[$this->position]);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * Set the position to a difined location.
   */
  public function seek($position) {
    $this->position = $position;
    return $this->current();
  }

}

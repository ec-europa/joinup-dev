<?php

namespace Drupal\pipeline;

/**
 * {@inheritdoc}
 */
class PipelineStepList implements PipelineStepListInterface {

  /**
   * An array of pipeline step definition.
   *
   * @var array
   */
  protected $list = [];

  /**
   * The current position in the list.
   *
   * @var int
   */
  protected $position = 0;

  /**
   * {@inheritdoc}
   */
  public function add($plugin_id) {
    $this->list[] = $plugin_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    if (!$this->valid()) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$this->position];
  }

  /**
   * {@inheritdoc}
   */
  public function get($position) {
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
   * {@inheritdoc}
   */
  public function seek($position) {
    $this->position = $position;
    return $this->current();
  }

}

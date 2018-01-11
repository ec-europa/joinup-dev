<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * {@inheritdoc}
 */
class PipelineStepDefinitionList implements PipelineStepDefinitionListInterface {
  protected $list = [];
  protected $position = 0;

  /**
   * {@inheritdoc}
   */
  public function add(string $plugin_id): PipelineStepDefinitionInterface {
    $this->list[] = new PipelineStepDefinition($plugin_id);
    $this->position = key($this->list);
    return $this->list[$this->position];
  }

  /**
   * {@inheritdoc}
   */
  public function first(): PipelineStepDefinitionInterface {
    return $this->list[0];
  }

  /**
   * {@inheritdoc}
   */
  public function current(): PipelineStepDefinitionInterface {
    if (!$this->valid()) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$this->position];
  }

  /**
   * {@inheritdoc}
   */
  public function get($position): PipelineStepDefinitionInterface {
    if (!isset($this->list[$position])) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$position];
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    ++$this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): int {
    return $this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return isset($this->list[$this->position]);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function seek($position): PipelineStepDefinitionInterface {
    $this->position = $position;
    return $this->current();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * {@inheritdoc}
 */
class RdfEtlStepList implements RdfEtlStepListInterface {

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
  public function add(string $plugin_id): RdfEtlStepListInterface {
    $this->list[] = $plugin_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function current(): string {
    if (!$this->valid()) {
      throw new \Exception('Cannot get out of bound element from pipeline definition list.');
    }
    return $this->list[$this->position];
  }

  /**
   * {@inheritdoc}
   */
  public function get(int $position): string {
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
  public function seek(int $position): string {
    $this->position = $position;
    return $this->current();
  }

}

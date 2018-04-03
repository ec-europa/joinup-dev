<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * A collection of steps to define the order of execution of a pipeline.
 */
interface RdfEtlStepListInterface extends \Iterator {

  /**
   * Adds a new step to the pipeline.
   *
   * @param string $plugin_id
   *   The plugin id of the data plugin to add to the pipeline.
   *
   * @return $this
   */
  public function add(string $plugin_id): self;

  /**
   * {@inheritdoc}
   */
  public function current(): string;

  /**
   * Returns a specified item from the list.
   *
   * @param int $position
   *   The index.
   *
   * @return string
   *   The pipeline step plugin ID.
   */
  public function get(int $position): string;

  /**
   * {@inheritdoc}
   */
  public function key(): int;

  /**
   * Set the iterator to the given position.
   *
   * @param int $position
   *   The position.
   *
   * @return string
   *   The pipeline step plugin ID for the specified position.
   */
  public function seek(int $position): string;

}

<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * A collection of steps to define the order of execution of a pipeline.
 */
interface PipelineStepDefinitionListInterface extends \Iterator {

  /**
   * Adds a new step to the pipeline.
   *
   * @param string $plugin_id
   *   The plugin id of the data plugin to add to the pipeline.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionInterface
   *   The pipeline step definition.
   */
  public function add(string $plugin_id): PipelineStepDefinitionInterface;

  /**
   * Fetches the first item from the list.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionInterface
   *   The pipeline step definition.
   */
  public function first(): PipelineStepDefinitionInterface;

  /**
   * {@inheritdoc}
   */
  public function current(): PipelineStepDefinitionInterface;

  /**
   * Returns a specified item from the list.
   *
   * @param int $position
   *   The index.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionInterface
   *   The pipeline step definition.
   *
   * @throws \Exception
   */
  public function get($position): PipelineStepDefinitionInterface;

  /**
   * {@inheritdoc}
   */
  public function next(): void;

  /**
   * {@inheritdoc}
   */
  public function key(): int;

  /**
   * {@inheritdoc}
   */
  public function valid(): bool;

  /**
   * {@inheritdoc}
   */
  public function rewind(): void;

  /**
   * Set the Iterator to the given position.
   *
   * @param int $position
   *   The position.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionInterface
   *   The pipeline step definition for the specified position.
   */
  public function seek(int $position): PipelineStepDefinitionInterface;

}

<?php

namespace Drupal\pipeline;

/**
 * Interface for the step batch progress.
 *
 * @package Drupal\pipeline
 */
interface PipelineStepBatchProgressInterface {

  /**
   * Whether the current batch has completed its iterations.
   *
   * @return bool
   *   The completion state of the batch.
   */
  public function getCompleted(): bool;

  /**
   * Mark the current batch as completed.
   *
   * @param bool $completed
   *   Whether the batch of the step has completed.
   */
  public function setCompleted($completed = TRUE);

  /**
   * Acts as a persistence mechanism for the step.
   *
   * Steps can use this to store arbitrary data in between batch iteration
   * executions.
   *
   * @return mixed
   *   The arbitrary data.
   */
  public function getData();

  /**
   * Retrieves the persisted arbitrary data for the step.
   *
   * Steps can use this to store arbitrary data in between batch iteration
   * executions.
   *
   * @param mixed $data
   *   The arbitrary data to persist.
   */
  public function setData($data): void;

  /**
   * Returns the total of iterations in the current batch.
   *
   * @return int
   *   Number of iterations.
   */
  public function getTotalBatchIterations(): int;

  /**
   * Sets the total number of iterations that the batch will process.
   *
   * @param int $totalIterations
   *   Amount of iterations in the batch.
   */
  public function setTotalBatchIterations(int $totalIterations);

  /**
   * Return the current batch iteration being processed.
   *
   * @return int
   *   The active batch iteration.
   */
  public function getBatchIteration(): int;

  /**
   * Sets the current batch iteration being processed.
   *
   * @param int $currentIteration
   *   The batch iteration being processed.
   */
  public function setBatchIteration(int $currentIteration);

  /**
   * Indicates if progress object needs to be populated with the iterations.
   *
   * @return bool
   *   Initialisation is needed.
   */
  public function needsInitialisation(): bool;

  /**
   * Returns a status message.
   *
   * @return string
   *   The status message.
   */
  public function getStatusMessage(): string;

  /**
   * Sets a batch status message.
   *
   * @param string $message
   *   The status message.
   */
  public function setStatusMessage(string $message): void;

}

<?php

declare(strict_types = 1);

namespace Drupal\pipeline;

/**
 * Provides an interface for pipeline state manager.
 */
interface PipelineStateManagerInterface {

  /**
   * Persists the pipeline state.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   * @param string $step_id
   *   The pipeline step plugin ID.
   *
   * @return $this
   */
  public function setState(string $pipeline_id, string $step_id): self;

  /**
   * Returns the current state for a given pipeline.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   *
   * @return string|null
   *   The step plugin ID or NULL.
   */
  public function getState(string $pipeline_id): ?string;

  /**
   * Persists the batch progress between requests.
   *
   * @param string $pipeline_id
   *   The active pipeline.
   * @param \Drupal\pipeline\PipelineStepBatchProgressInterface $batch_progress
   *   The batch progress object.
   */
  public function setBatchProgress(string $pipeline_id, PipelineStepBatchProgressInterface $batch_progress);

  /**
   * Retrieves the persisted batch progress.
   *
   * @param string $pipeline_id
   *   The active pipeline.
   *
   * @return \Drupal\pipeline\PipelineStepBatchProgressInterface
   *   The batch progress object.
   */
  public function getBatchProgress(string $pipeline_id): PipelineStepBatchProgressInterface;

  /**
   * Deletes the persisted state for a given pipeline.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   *
   * @return $this
   */
  public function reset(string $pipeline_id): self;

  /**
   * Clears the persisted state of the batch process.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   */
  public function resetBatchProgress(string $pipeline_id);

  /**
   * Returns metadata about the persisted state.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   *
   * @return \stdClass|null
   *   An object with the owner and updated time if the key has a value, or
   *   NULL otherwise.
   */
  public function getStateMetadata(string $pipeline_id): ?\stdClass;

}

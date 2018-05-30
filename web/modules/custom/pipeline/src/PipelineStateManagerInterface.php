<?php

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
  public function setState($pipeline_id, $step_id);

  /**
   * Returns the current state for a given pipeline.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   *
   * @return string|null
   *   The step plugin ID or NULL.
   */
  public function getState($pipeline_id);

  /**
   * Deletes the persisted state for a given pipeline.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   *
   * @return $this
   */
  public function reset($pipeline_id);

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
  public function getStateMetadata($pipeline_id);

}

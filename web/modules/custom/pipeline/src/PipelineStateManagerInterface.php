<?php

namespace Drupal\pipeline;

/**
 * Provides an interface for pipeline state manager.
 */
interface PipelineStateManagerInterface {

  /**
   * Checks whether a persisted state is available.
   *
   * @return bool
   *   The persistence state.
   */
  public function isPersisted();

  /**
   * Persists the pipeline state for a following request.
   *
   * @param \Drupal\pipeline\PipelineState $state
   *   The state object to persist.
   *
   * @return $this
   */
  public function setState(PipelineState $state);

  /**
   * Returns the current state.
   *
   * @return \Drupal\pipeline\PipelineState
   *   The state value object.
   */
  public function getState();

  /**
   * Deletes the persisted state.
   *
   * @return $this
   */
  public function reset();

}

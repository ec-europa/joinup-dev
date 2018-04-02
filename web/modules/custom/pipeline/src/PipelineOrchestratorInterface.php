<?php

namespace Drupal\pipeline;

/**
 * Provides an interface for the pipeline orchestrator.
 */
interface PipelineOrchestratorInterface {

  /**
   * Executes the orchestrator.
   *
   * @param string $pipeline
   *   The pipeline to be used.
   *
   * @return mixed
   *   The response.
   */
  public function run($pipeline);

  /**
   * Resets the state machine.
   */
  public function reset();

}

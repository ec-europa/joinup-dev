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
  public function run(string $pipeline);

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   */
  public function reset();

}

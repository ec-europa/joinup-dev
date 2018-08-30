<?php

namespace Drupal\pipeline;

/**
 * Provides an interface for the pipeline orchestrator.
 */
interface PipelineOrchestratorInterface {

  /**
   * Executes a given pipeline.
   *
   * @param string $pipeline_id
   *   The pipeline plugin ID.
   *
   * @return mixed
   *   The response.
   */
  public function run($pipeline_id);

  /**
   * Resets a given pipeline.
   *
   * @param string $pipeline
   *   The pipeline to reset.
   */
  public function reset($pipeline);

}

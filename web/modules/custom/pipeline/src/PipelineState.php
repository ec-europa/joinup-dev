<?php

namespace Drupal\pipeline;

/**
 * Class representing a state of a pipeline.
 */
class PipelineState {

  /**
   * The pipeline plugin ID.
   *
   * @var string
   */
  protected $pipeline;

  /**
   * The pipeline step.
   *
   * @var string|null
   */
  protected $step;

  /**
   * Creates a new state object.
   *
   * @param string $pipeline
   *   The pipeline plugin id.
   * @param int $step_plugin_id
   *   The step of the pipeline.
   */
  public function __construct($pipeline, $step_plugin_id) {
    $this->pipeline = $pipeline;
    $this->step = $step_plugin_id;
  }

  /**
   * Returns the persisted step within the pipeline.
   *
   * @return string|null
   *   The current step within the pipeline.
   */
  public function getStep() {
    return $this->step;
  }

  /**
   * Returns the persisted pipeline ID.
   *
   * @return string
   *   The plugin id of the pipeline.
   */
  public function getPipelineId() {
    return $this->pipeline;
  }

}

<?php

namespace Drupal\rdf_etl;

/**
 * Class EtlState.
 *
 * @package Drupal\rdf_etl
 */
class EtlState {
  protected $pipeline;
  protected $sequence;

  /**
   * EtlState constructor.
   *
   * @param string $pipeline
   *   The pipeline plugin id.
   * @param int $sequence
   *   The sequence of the pipeline.
   */
  public function __construct($pipeline, $sequence) {
    $this->pipeline = $pipeline;
    $this->sequence = $sequence;
  }

  /**
   * Get the persisted position within the pipeline.
   *
   * @return int
   *   The current position of within the pipeline.
   */
  public function sequence(): int {
    return (int) $this->sequence;
  }

  /**
   * Get the persisted pipeline id.
   *
   * @return string
   *   The plugin id of the pipeline.
   */
  public function pipelineId(): String {
    return $this->pipeline;
  }

}

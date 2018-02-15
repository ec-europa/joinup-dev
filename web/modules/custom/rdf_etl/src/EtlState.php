<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * Class representing a state of a pipeline.
 *
 * @package Drupal\rdf_etl
 */
class EtlState {

  /**
   * The pipeline plugin id.
   *
   * @var string
   */
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
  public function __construct(string $pipeline, int $sequence) {
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

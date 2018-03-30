<?php

namespace Drupal\pipeline;

use Drupal\Core\State\StateInterface;

/**
 * Manages the pipeline state.
 */
class PipelineStateManager implements PipelineStateManagerInterface {

  /**
   * The Drupal state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The offset of the active step.
   *
   * @var int
   */
  protected $sequence;

  /**
   * The pipeline plugin id.
   *
   * @var string
   */
  protected $pipeline;

  /**
   * {@inheritdoc}
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
    $this->pipeline = $this->state->get('pipeline.active_pipeline');
    $this->sequence = $this->state->get('pipeline.active_pipeline_sequence');
  }

  /**
   * {@inheritdoc}
   */
  public function isPersisted() {
    return isset($this->pipeline) && isset($this->sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function setState(PipelineState $state) {
    $this->pipeline = $state->getPipelineId();
    $this->state->set('pipeline.active_pipeline', $this->pipeline);
    $this->sequence = $state->sequence();
    $this->state->set('pipeline.active_pipeline_sequence', $this->sequence);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return new PipelineState($this->pipeline, $this->sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->state->delete('pipeline.active_pipeline_sequence');
    $this->state->delete('pipeline.active_pipeline');
    $this->pipeline = NULL;
    $this->sequence = NULL;
    return $this;
  }

}

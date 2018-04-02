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
   * The active step.
   *
   * @var string
   */
  protected $step;

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
    $this->pipeline = $this->state->get('pipeline.pipeline');
    $this->step = $this->state->get('pipeline.sequence');
  }

  /**
   * {@inheritdoc}
   */
  public function isPersisted() {
    return isset($this->pipeline) && isset($this->step);
  }

  /**
   * {@inheritdoc}
   */
  public function setState(PipelineState $state) {
    $this->pipeline = $state->getPipelineId();
    $this->state->set('pipeline.pipeline', $this->pipeline);
    $this->step = $state->getStep();
    $this->state->set('pipeline.sequence', $this->step);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return new PipelineState($this->pipeline, $this->step);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->state->delete('pipeline.sequence');
    $this->state->delete('pipeline.pipeline');
    $this->pipeline = NULL;
    $this->step = NULL;
    return $this;
  }

}

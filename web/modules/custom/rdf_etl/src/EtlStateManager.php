<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

use Drupal\Core\State\StateInterface;

/**
 * Class EtlState.
 */
class EtlStateManager implements EtlStateManagerInterface {

  /**
   * The Drupal state system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  protected $sequence;

  protected $pipeline;

  /**
   * EtlStateManager constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
    $this->pipeline = $this->state->get('rdf_etl.active_pipeline');
    $this->sequence = $this->state->get('rdf_etl.active_pipeline_sequence');
  }

  /**
   * Whether a persisted state is available.
   *
   * @return bool
   *   The persistence state.
   */
  public function isPersisted() {
    return isset($this->pipeline) && isset($this->sequence);
  }

  /**
   * Persists the pipeline state for a following request.
   *
   * @param \Drupal\rdf_etl\EtlState $state
   *   The state object to persist.
   *
   * @return $this
   */
  public function setState(EtlState $state): EtlStateManager {
    $this->pipeline = $state->pipelineId();
    $this->state->set('rdf_etl.active_pipeline', $this->pipeline);
    $this->sequence = $state->sequence();
    $this->state->set('rdf_etl.active_pipeline_sequence', $this->sequence);
    return $this;
  }

  /**
   * Returns the current state.
   *
   * @return \Drupal\rdf_etl\EtlState
   *   The state value object.
   */
  public function state() {
    return new EtlState($this->pipeline, $this->sequence);
  }

  /**
   * Delete the persisted state.
   *
   * @return $this
   */
  public function reset() {
    $this->state->delete('rdf_etl.active_pipeline_sequence');
    $this->state->delete('rdf_etl.active_pipeline');
    $this->pipeline = NULL;
    $this->sequence = NULL;
    return $this;
  }

}

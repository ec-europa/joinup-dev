<?php

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

  protected $step;

  protected $pipeline;

  public function __construct(StateInterface $state) {
    $this->state = $state;
    $this->step = $this->state->get('rdf_etl.active_step');
    $this->pipeline = $this->state->get('rdf_etl.active_pipeline');
  }

  public function isPersisted() {
    return isset($this->state) && isset($this->step);
  }

  public function setState(String $pipeline_id, String $step_id) {
    $this->pipeline = $pipeline_id;
    $this->state->set('rdf_etl.active_pipeline', $pipeline_id);
    $this->step = $step_id;
    $this->state->set('rdf_etl.active_step', $step_id);
    return $this;
  }

  public function getPersistedStep() : string {
    return $this->step;
  }

  public function getPersistedPipeline() {
    return $this->pipeline;
  }

  public function reset() {
    $this->state->delete('rdf_etl.active_step');
    $this->state->delete('rdf_etl.active_pipeline');
    $this->pipeline = NULL;
    $this->step = NULL;
    return $this;
  }

}

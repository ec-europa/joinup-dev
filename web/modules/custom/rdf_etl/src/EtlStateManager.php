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

  protected $activeStep;

  protected $pipeline;

  public function __construct(StateInterface $state) {
    $this->state = $state;
    $this->activeStep = $this->state->get('rdf_etl.active_step');
    $this->pipeline = $this->state->get('rdf_etl.active_pipeline');
  }

  public function isInitialized() {
    return isset($this->state) && isset($this->activeStep);
  }

  public function setActiveStep(String $step) {
    $this->activeStep = $step;
    $this->state->set('rdf_etl.active_step', $step);
    return $this;
  }

  public function setActivePipeline(String $pipeline) {
    $this->pipeline = $pipeline;
    $this->state->set('rdf_etl.active_pipeline', $pipeline);
    return $this;
  }

  public function getActiveStep() {
    return $this->activeStep;
  }

  public function getActivePipeline() {
    return $this->pipeline;
  }

  public function reset() {
    $this->state->delete('rdf_etl.active_step');
    $this->state->delete('rdf_etl.active_pipeline');
    $this->pipeline = NULL;
    $this->activeStep = NULL;
    return $this;
  }

}

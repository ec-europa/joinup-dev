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
    $this->pipeline = $this->state->get('rdf_etl.active_step');
  }

  public function setActiveStep(String $step) {
    $this->activeStep = $step;
    $this->state->set('rdf_etl.active_step', $step);
    return $this->getImmutable();
  }

  public function setActivePipeline(String $pipeline) {
    $this->activeStep = $pipeline;
    $this->state->set('rdf_etl.active_pipeline', $pipeline);
    return $this->getImmutable();
  }

  public function getImmutable() {
    return new EtlState(
      $this->activeStep,
      $this->pipeline
    );
  }

}

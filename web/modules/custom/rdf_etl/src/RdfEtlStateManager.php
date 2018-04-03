<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

use Drupal\Core\State\StateInterface;

/**
 * Class EtlState.
 */
class RdfEtlStateManager implements RdfEtlStateManagerInterface {

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
    $this->pipeline = $this->state->get('rdf_etl.active_pipeline');
    $this->sequence = $this->state->get('rdf_etl.active_pipeline_sequence');
  }

  /**
   * {@inheritdoc}
   */
  public function isPersisted(): bool {
    return isset($this->pipeline) && isset($this->sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function setState(RdfEtlState $state): RdfEtlStateManagerInterface {
    $this->pipeline = $state->getPipelineId();
    $this->state->set('rdf_etl.active_pipeline', $this->pipeline);
    $this->sequence = $state->sequence();
    $this->state->set('rdf_etl.active_pipeline_sequence', $this->sequence);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function state(): RdfEtlState {
    return new RdfEtlState($this->pipeline, $this->sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): RdfEtlStateManagerInterface {
    $this->state->delete('rdf_etl.active_pipeline_sequence');
    $this->state->delete('rdf_etl.active_pipeline');
    $this->pipeline = NULL;
    $this->sequence = NULL;
    return $this;
  }

}

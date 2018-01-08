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
    $this->sequence = $this->state->get('rdf_etl.active_sequence');
    $this->pipeline = $this->state->get('rdf_etl.active_pipeline_sequence');
  }

  /**
   * Whether a persisted state is available.
   *
   * @return bool
   *   The persistence state.
   */
  public function isPersisted() {
    return isset($this->state) && isset($this->sequence);
  }

  /**
   * Persists the pipeline state for a following request.
   *
   * @param string $pipeline_id
   *   The plugin id of the pipeline.
   * @param int $sequence
   *   The position of where we are in the execution process.
   *
   * @return $this
   */
  public function setState(string $pipeline_id, int $sequence) : EtlStateManager {
    $this->pipeline = $pipeline_id;
    $this->state->set('rdf_etl.active_pipeline', $pipeline_id);
    $this->sequence = $sequence;
    $this->state->set('rdf_etl.active_pipeline_sequence', $sequence);
    return $this;
  }

  /**
   * Get the persisted position within the pipeline.
   *
   * @return int
   *   The current position of within the pipeline.
   */
  public function getPersistedPipelineSequence() : int {
    return (int) $this->sequence;
  }

  /**
   * Get the persisted pipeline id.
   *
   * @return string
   *   The plugin id of the pipeline.
   */
  public function getPersistedPipelineId() : String {
    return $this->pipeline;
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

<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl;

/**
 * Class EtlState.
 */
interface EtlStateManagerInterface {

  /**
   * Whether a persisted state is available.
   *
   * @return bool
   *   The persistence state.
   */
  public function isPersisted(): bool;

  /**
   * Persists the pipeline state for a following request.
   *
   * @param \Drupal\rdf_etl\EtlState $state
   *   The state object to persist.
   *
   * @return $this
   */
  public function setState(EtlState $state): EtlStateManagerInterface;

  /**
   * Returns the current state.
   *
   * @return \Drupal\rdf_etl\EtlState
   *   The state value object.
   */
  public function state(): EtlState;

  /**
   * Delete the persisted state.
   *
   * @return $this
   */
  public function reset(): EtlStateManagerInterface;

}

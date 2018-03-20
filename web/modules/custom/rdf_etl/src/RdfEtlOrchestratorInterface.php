<?php

namespace Drupal\rdf_etl;

/**
 * Class EtlOrchestrator.
 */
interface RdfEtlOrchestratorInterface {

  /**
   * Executes the orchestrator.
   *
   * @param string $pipeline
   *   The pipeline to be used.
   *
   * @return mixed
   *   The response.
   */
  public function run(string $pipeline);

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   */
  public function reset(): void;

}

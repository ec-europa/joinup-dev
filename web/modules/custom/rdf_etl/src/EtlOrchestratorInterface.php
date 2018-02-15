<?php

namespace Drupal\rdf_etl;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Class EtlOrchestrator.
 */
interface EtlOrchestratorInterface {

  /**
   * Execute the orchestrator.
   *
   * @return mixed
   *   The response.
   */
  public function run();

  /**
   * Controller callback: Reset the state machine.
   *
   * Should not be used, unless something went really bad.
   */
  public function reset(): void;

  /**
   * Get the label of the active pipeline.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A markup object with the label of the active pipeline.
   */
  public function getActivePipelineLabel(): TranslatableMarkup;

  /**
   * Get the label of the active step.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   A markup object with the label of the active step.
   */
  public function getActiveStepLabel(): TranslatableMarkup;

}

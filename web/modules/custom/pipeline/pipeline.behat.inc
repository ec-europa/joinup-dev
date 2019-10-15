<?php

/**
 * @file
 * Contains \PipelineSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Behat step definitions for testing the pipeline.
 */
class PipelineSubContext extends DrupalSubContextBase {

  /**
   * Reset the pipeline orchestrator through the API.
   *
   * @param string $pipeline
   *   The pipeline ID.
   *
   * @Given I reset the :pipeline pipeline
   */
  public function iResetThePipelineOrchestrator($pipeline) {
    $this->visitPath("admin/content/pipeline/$pipeline/reset");
  }

}

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
   * Navigates to the pipeline orchestrator.
   *
   * @When (I )go to the pipeline orchestrator
   * @When (I )visit the pipeline orchestrator
   * @When I am on the pipeline orchestrator
   */
  public function visitPipelineOrchestrator() {
    $this->visitPath('admin/content/pipeline');
  }

  /**
   * Reset the pipeline orchestrator through the API.
   *
   * @param string $pipeline
   *   The pipeline ID.
   *
   * @Given I reset the :pipeline pipeline
   */
  public function iResetThePipelineOrchestrator($pipeline) {
    $this->visitPath("admin/content/$pipeline/reset");
  }

}

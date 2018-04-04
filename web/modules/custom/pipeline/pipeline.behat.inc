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
   * @Given I reset the pipeline orchestrator
   */
  public function iResetThePipelineOrchestrator() {
    /** @var \Drupal\pipeline\PipelineOrchestrator $orchestrator */
    $orchestrator = \Drupal::service('pipeline.orchestrator');
    $orchestrator->reset();
  }

}

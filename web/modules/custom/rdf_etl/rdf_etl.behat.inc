<?php

/**
 * @file
 * Contains \RdfEtlSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Behat step definitions for testing the etl pipeline.
 */
class RdfEtlSubContext extends DrupalSubContextBase {

  /**
   * Navigates to the etl orchestrator.
   *
   * @When (I )go to the etl orchestrator
   * @When (I )visit the etl orchestrator
   * @When I am on the etl orchestrator
   */
  public function visitEtlOrchestrator() {
    $this->visitPath('admin/content/etl');
  }

  /**
   * Reset the orchestrator through the API.
   *
   * @Given I reset the orchestrator
   */
  public function iResetTheOrchestrator() {
    /** @var \Drupal\rdf_etl\RdfEtlOrchestrator $orchestrator */
    $orchestrator = \Drupal::service('rdf_etl.orchestrator');
    $orchestrator->reset();
  }

}

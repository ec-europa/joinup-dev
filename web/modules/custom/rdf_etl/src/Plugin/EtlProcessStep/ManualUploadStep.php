<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\rdf_etl\ProcessStepBase;

/**
 * Defines a manual data upload step.
 *
 * @EtlProcessStep(
 *  id = "manual_upload_step",
 *  label = @Translation("Manual upload"),
 * )
 */
class ManualUploadStep extends ProcessStepBase {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // TODO: Implement execute() method.
  }

}

<?php

namespace Drupal\rdf_etl\Plugin\EtlProcessStep;

use Drupal\Component\Plugin\PluginBase;
use Drupal\rdf_etl\Plugin\EtlProcessStepInterface;

/**
 * Defines a manual data upload step.
 *
 * @EtlProcessStep(
 *  id = "manual_upload_step",
 *  label = @Translation("Manual upload"),
 * )
 */
class ManualUploadStep extends PluginBase implements EtlProcessStepInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Implement your logic.
    return $build;
  }

}

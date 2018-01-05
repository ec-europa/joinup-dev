<?php

namespace Drupal\spain_ctt\Plugin\EtlDataPipeline;

use Drupal\rdf_etl\Plugin\EtlDataPipelineInterface;


/**
 * @EtlDataPipeline(
 *  id = "pipeline_spain",
 *  label = @Translation("Spain - Center for Technology Transfer"),
 * )
 */
class SpainCttDataPipeline implements EtlDataPipelineInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    // Implement your logic.

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    // Gets the plugin_id of the plugin instance.
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    // Gets the definition of the plugin implementation.
  }

  function getStepDefinitions() {
    return [
      'manual_upload_step'
    ];
  }


}

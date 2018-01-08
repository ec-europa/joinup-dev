<?php

namespace Drupal\spain_ctt\Plugin\EtlDataPipeline;

use Drupal\rdf_etl\PipelineStepDefinitionList;
use Drupal\rdf_etl\Plugin\EtlDataPipelineBase;

/**
 * The data pipeline of Spanish repository.
 *
 * @EtlDataPipeline(
 *  id = "pipeline_spain",
 *  label = @Translation("Spain - Center for Technology Transfer"),
 * )
 */
class SpainCttDataPipeline extends EtlDataPipelineBase {

  /**
   * {@inheritdoc}
   */
  protected function initStepDefinition() {
    $this->steps = new PipelineStepDefinitionList();
    $this->steps->add('manual_upload_step');
  }

}

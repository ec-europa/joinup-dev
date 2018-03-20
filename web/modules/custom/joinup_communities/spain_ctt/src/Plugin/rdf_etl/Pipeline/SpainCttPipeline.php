<?php

namespace Drupal\spain_ctt\Plugin\rdf_etl\Pipeline;

use Drupal\rdf_etl\Plugin\RdfEtlPipelinePluginBase;

/**
 * The data pipeline of Spanish repository.
 *
 * @RdfEtlPipeline(
 *  id = "spain",
 *  label = @Translation("Spain - Center for Technology Transfer"),
 * )
 */
class SpainCttPipeline extends RdfEtlPipelinePluginBase {

  /**
   * {@inheritdoc}
   */
  protected function initStepDefinition(): void {
    $this->steps->add('manual_upload_step');
    $this->steps->add('convert_to_adms2');
    $this->steps->add('adms_validation');
  }

}

<?php

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\rdf_etl\PipelineStepDefinitionList;

/**
 * Base class for Data pipeline plugins.
 */
abstract class EtlDataPipelineBase extends PluginBase implements EtlDataPipelineInterface {

  /** @var \Drupal\rdf_etl\PipelineStepDefinitionList */
  public $steps;

  /**
   * Get steps.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionList
   *   The step definition.
   */
  public function getSteps() : PipelineStepDefinitionList {
    if (!isset($this->steps)) {
      $this->initStepDefinition();
    }
    return $this->steps;
  }

  /**
   * Initializes steps to a PipelineStepDefinitionList.
   */
  abstract protected function initStepDefinition();

}

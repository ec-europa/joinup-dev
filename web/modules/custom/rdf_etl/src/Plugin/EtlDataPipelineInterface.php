<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\rdf_etl\PipelineStepDefinitionList;

/**
 * Defines an interface for Data pipeline plugins.
 */
interface EtlDataPipelineInterface extends PluginInspectionInterface {

  /**
   * Gets the sequence in which the data processing steps should be performed.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionList
   *   The sequence definition.
   */
  public function stepDefinitionList(): PipelineStepDefinitionList;

}

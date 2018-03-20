<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\rdf_etl\PipelineStepDefinitionInterface;
use Drupal\rdf_etl\PipelineStepDefinitionList;

/**
 * Defines an interface for Data pipeline plugins.
 */
interface RdfEtlPipelineInterface extends PluginInspectionInterface {

  /**
   * Gets the sequence in which the data processing steps should be performed.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionList
   *   The sequence definition.
   */
  public function stepDefinitionList(): PipelineStepDefinitionList;

  /**
   * Returns an individual step definition.
   *
   * @param int $sequence
   *   The offset in the list.
   *
   * @return \Drupal\rdf_etl\PipelineStepDefinitionInterface
   *   The step definition.
   */
  public function getStepDefinition(int $sequence): PipelineStepDefinitionInterface;

  /**
   * Sets step-iterator to the given position.
   *
   * @param int $sequence
   *   The position in the flow.
   */
  public function setActiveStepDefinition(int $sequence): void;

}

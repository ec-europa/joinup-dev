<?php

declare(strict_types = 1);

namespace Drupal\rdf_etl\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\rdf_etl\PipelineStepDefinitionInterface;
use Drupal\rdf_etl\PipelineStepDefinitionList;

/**
 * Base class for Data pipeline plugins.
 */
abstract class EtlDataPipelineBase extends PluginBase implements EtlDataPipelineInterface {

  /**
   * The execution order of the pipeline.
   *
   * @var \Drupal\rdf_etl\PipelineStepDefinitionList
   */
  public $steps;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->steps = new PipelineStepDefinitionList();
    $this->initStepDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function stepDefinitionList(): PipelineStepDefinitionList {
    return $this->steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepDefinition(int $sequence): PipelineStepDefinitionInterface {
    return $this->steps->get($sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveStepDefinition(int $sequence): void {
    $this->steps->seek($sequence);
  }

  /**
   * Initializes steps to a PipelineStepDefinitionList.
   */
  abstract protected function initStepDefinition(): void;

}

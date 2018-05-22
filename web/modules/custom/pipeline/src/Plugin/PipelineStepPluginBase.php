<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for the for pipeline step plugins.
 */
abstract class PipelineStepPluginBase extends PluginBase implements PipelineStepInterface, ConfigurablePluginInterface {

  use StringTranslationTrait;

  /**
   * The parent pipeline.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelineInterface
   */
  protected $pipeline;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$data) {
    if (!isset($this->pipeline)) {
      throw new \RuntimeException("The step cannot be executed because no pipeline is set.");
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPipeline(PipelinePipelineInterface $pipeline) {
    $this->pipeline = $pipeline;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPipeline() {
    return $this->pipeline;
  }

  /**
   * {@inheritdoc}
   */
  public function onPipelineSuccess() {}

  /**
   * {@inheritdoc}
   */
  public function onPipelineError() {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Ensure sane defaults.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}

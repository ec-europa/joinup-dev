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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    $this->ensurePipeline();
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

  /**
   * {@inheritdoc}
   */
  public function getPersistentData() {
    $this->ensurePipeline();
    return $this->pipeline->getCurrentState()->getData();
  }

  /**
   * {@inheritdoc}
   */
  public function hasPersistentDataValue($key) {
    $data = $this->getPersistentData();
    return array_key_exists($key, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentDataValue($key) {
    $data = $this->getPersistentData();
    if (!array_key_exists($key, $data)) {
      throw new \InvalidArgumentException("There's no '$key' key in persistent data.");
    }
    return $data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function setPersistentData(array $data) {
    $this->ensurePipeline();
    $this->pipeline->getCurrentState()->setData($data);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersistentDataValue($key, $value) {
    $this->ensurePipeline();
    $this->pipeline->getCurrentState()->setDataValue($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearPersistentData() {
    $this->ensurePipeline();
    $this->pipeline->getCurrentState()->clearData();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetPersistentDataValue($key) {
    $this->ensurePipeline();
    $this->pipeline->getCurrentState()->unsetDataValue($key);
    return $this;
  }

  /**
   * Ensures a pipeline was set.
   *
   * @throws \LogicException
   *   If the pipeline was not set.
   */
  protected function ensurePipeline() {
    if (empty($this->pipeline)) {
      throw new \LogicException("This method should be called only after the pipeline was set for this step.");
    }
  }

}

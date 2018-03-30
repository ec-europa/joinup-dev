<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pipeline\PipelineStepList;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for pipeline plugins.
 */
abstract class PipelinePipelinePluginBase extends PluginBase implements PipelinePipelineInterface, ContainerFactoryPluginInterface {

  use DependencySerializationTrait;

  /**
   * The execution order of the pipeline.
   *
   * @var \Drupal\pipeline\PipelineStepList
   */
  protected $steps;

  /**
   * The step plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelineStepPluginManager
   */
  protected $stepPluginManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\pipeline\Plugin\PipelineStepPluginManager $step_plugin_manager
   *   The step plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stepPluginManager = $step_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.pipeline_step')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function onAfterPipelineExecute() {
    // Ask each step if they want to take some action after pipeline execution.
    foreach ($this->getStepList() as $step_plugin_id) {
      /** @var \Drupal\pipeline\Plugin\PipelineStepInterface $step_plugin */
      $step_plugin = $this->stepPluginManager->createInstance($step_plugin_id);
      $step_plugin->onAfterPipelineExecute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStepList() {
    if (!isset($this->steps)) {
      $this->steps = new PipelineStepList();
      foreach ($this->getPluginDefinition()['steps'] as $step_plugin_id) {
        if (!\Drupal::service('plugin.manager.pipeline_step')->hasDefinition($step_plugin_id)) {
          throw new \InvalidArgumentException("Invalid step plugin '$step_plugin_id'.");
        }
        $this->steps->add($step_plugin_id);
      }
      if (!iterator_count($this->steps)) {
        throw new \InvalidArgumentException("Pipeline '{$this->getPluginId()}' has no valid steps.");
      }
    }
    return $this->steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepPluginId($sequence) {
    return $this->getStepList()->get($sequence);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveStep($sequence): void {
    $this->getStepList()->seek($sequence);
  }

}

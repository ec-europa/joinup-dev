<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\pipeline\PipelineStateInterface;
use Drupal\pipeline\PipelineStateManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for pipeline plugins.
 */
abstract class PipelinePipelinePluginBase extends PluginBase implements PipelinePipelineInterface, ContainerFactoryPluginInterface, ConfigurablePluginInterface {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The pipeline steps.
   *
   * @var \ArrayIterator
   */
  protected $steps;

  /**
   * The step plugin manager service.
   *
   * @var \Drupal\pipeline\Plugin\PipelineStepPluginManager
   */
  protected $stepPluginManager;

  /**
   * The pipeline state manager service.
   *
   * @var \Drupal\pipeline\PipelineStateManager
   */
  protected $stateManager;

  /**
   * The current pipeline state.
   *
   * @var \Drupal\pipeline\PipelineStateInterface
   */
  protected $state;

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
   * @param \Drupal\pipeline\PipelineStateManager $state_manager
   *   The pipeline state manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PipelineStepPluginManager $step_plugin_manager, PipelineStateManager $state_manager) {
    $this->normalizeStepList($plugin_definition);

    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
    $this->stepPluginManager = $step_plugin_manager;
    $this->stateManager = $state_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.pipeline_step'),
      $container->get('pipeline.state_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createStepInstance($step_plugin_id) {
    /** @var \Drupal\pipeline\Plugin\PipelineStepInterface $step */
    $step = $this->stepPluginManager->createInstance($step_plugin_id, $this->getStepList()->offsetGet($step_plugin_id));
    return $step->setPipeline($this);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {}

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
    // Ask each step if they want to take some action after pipeline execution.
    foreach ($this->getStepList() as $step_plugin_id => $step_plugin_config) {
      /** @var \Drupal\pipeline\Plugin\PipelineStepInterface $step_plugin */
      $step_plugin = $this->stepPluginManager->createInstance($step_plugin_id, $step_plugin_config);
      $step_plugin->onPipelineSuccess();
    }
    // Reset the state manager.
    $this->stateManager->reset($this->getPluginId());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessMessage() {
    return [
      '#markup' => $this->t("%pipeline finished with success.", [
        '%pipeline' => $this->getPluginDefinition()['label'],
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function onError() {
    // Ask each step if they want to take some action after pipeline error.
    foreach ($this->getStepList() as $step_plugin_id => $step_plugin_config) {
      /** @var \Drupal\pipeline\Plugin\PipelineStepInterface $step_plugin */
      $step_plugin = $this->stepPluginManager->createInstance($step_plugin_id, $step_plugin_config);
      $step_plugin->onPipelineError();
    }
    // Reset the state manager.
    $this->stateManager->reset($this->getPluginId());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->stateManager->reset($this->getPluginId());
  }

  /**
   * Gets the steps internal iterator.
   *
   * @return \ArrayIterator
   *   The steps iterator.
   *
   * @throws \InvalidArgumentException
   *   The steps defined in annotation are invalid or no steps are provided.
   */
  protected function getStepList() {
    if (!isset($this->steps)) {
      $steps = $this->getPluginDefinition()['steps'];
      foreach ($steps as $step_plugin_id => $step_plugin_config) {
        if (!\Drupal::service('plugin.manager.pipeline_step')->hasDefinition($step_plugin_id)) {
          throw new \InvalidArgumentException("Invalid step plugin '$step_plugin_id'.");
        }
      }
      if (!count($steps)) {
        throw new \InvalidArgumentException("Pipeline '{$this->getPluginId()}' has no valid steps.");
      }
      $this->steps = new \ArrayIterator($steps, 1);
    }
    return $this->steps;
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->getStepList()->next();
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    return $this->getStepList()->valid();
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->getStepList()->current();
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->getStepList()->rewind();
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->getStepList()->key();
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentState(PipelineStateInterface $state) {
    $this->rewind();
    while ($this->valid()) {
      if ($this->key() === $state->getStepId()) {
        $this->state = $state;
        return $this;
      }
      $this->next();
    }
    throw new \InvalidArgumentException("Step '{$state->getStepId()}' doesn't exist.");
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentState() {
    return $this->state;
  }

  /**
   * {@inheritdoc}
   */
  public function saveCurrentState() {
    $this->stateManager->setState($this->getPluginId(), $this->getCurrentState());
  }

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
   * Normalizes the steps list.
   *
   * Changes the plugin definition 'steps' entry from a list, such as:
   * @codingStandardsIgnoreStart
   * [
   *   'foo',
   *   'bar' => [
   *     'baz',
   *     'qux',
   *   ],
   *   'foobar',
   * ]
   * @codingStandardsIgnoreEnd
   * into:
   * @codingStandardsIgnoreStart
   * [
   *   'foo' => [],
   *   'bar' => [
   *     'baz',
   *     'qux',
   *   ],
   *   'foobar' => [],
   * ]
   * @codingStandardsIgnoreEnd
   *
   * @param array $plugin_definition
   *   The plugin definition to be altered.
   */
  protected function normalizeStepList(array &$plugin_definition) {
    $steps = [];
    foreach ($plugin_definition['steps'] as $delta => $step) {
      $is_preconfigured = is_array($step);
      $key = $is_preconfigured ? $delta : $step;
      $value = $is_preconfigured ? $step : [];
      $steps[$key] = $value;
    }
    $plugin_definition['steps'] = $steps;
  }

}

<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\pipeline\PipelineStateInterface;

/**
 * Defines an interface for pipeline plugins.
 */
interface PipelinePipelineInterface extends PluginInspectionInterface, \Iterator {

  /**
   * Sets steps iterator pointer to a given step.
   *
   * @param \Drupal\pipeline\PipelineStateInterface $state
   *   The current state object.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If the state step ID is invalid.
   */
  public function setCurrentState(PipelineStateInterface $state);

  /**
   * The current pipeline state.
   *
   * @return \Drupal\pipeline\PipelineStateInterface
   *   The pipeline state object.
   */
  public function getCurrentState();

  /**
   * Persists the pipeline current state.
   *
   * @return $this
   */
  public function saveCurrentState();

  /**
   * Creates a step plugin instance in this pipeline.
   *
   * @param string $step_plugin_id
   *   The step plugin ID.
   *
   * @return \Drupal\pipeline\Plugin\PipelineStepInterface
   *   The step plugin instance.
   */
  public function createStepInstance($step_plugin_id);

  /**
   * Gives a chance to plugins to perform some tasks just before executing.
   *
   * @return null|\Drupal\Component\Render\MarkupInterface|string
   *   If no errors were encountered during the pipeline preparation, nothing
   *   should be returned. Return the error message as a translatable markup
   *   object.
   */
  public function prepare();

  /**
   * Runs specific code after the pipeline is executed with success.
   *
   * @return array|null
   *   A render array could be returned to be displayed as content of the
   *   success page.
   */
  public function onSuccess();

  /**
   * Runs specific code after the pipeline exits with error.
   *
   * @return $this
   */
  public function onError();

  /**
   * Acts when a reset action occurs.
   */
  public function reset();

}

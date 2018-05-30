<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for pipeline plugins.
 */
interface PipelinePipelineInterface extends PluginInspectionInterface, \Iterator {

  /**
   * Sets steps iterator pointer to a given step.
   *
   * @param string $step_plugin_id
   *   The step plugin ID.
   */
  public function setCurrent($step_plugin_id);

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
   * @return $this
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

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
   * Runs specific code after the pipeline is executed with success.
   */
  public function onSuccess();

  /**
   * Runs specific code after the pipeline exits with error.
   */
  public function onError();

}

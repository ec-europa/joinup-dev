<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for pipeline plugins.
 */
interface PipelinePipelineInterface extends PluginInspectionInterface {

  /**
   * Runs specific code after the pipeline is executed with success.
   */
  public function onAfterPipelineExecute();

  /**
   * Gets the sequence in which the data processing steps should be performed.
   *
   * @return \Drupal\pipeline\PipelineStepList
   *   The sequence definition.
   */
  public function getStepList();

  /**
   * Returns an individual step definition.
   *
   * @param int $sequence
   *   The offset in the list.
   *
   * @return string
   *   The step definition.
   */
  public function getStepPluginId($sequence);

  /**
   * Sets step-iterator to the given position.
   *
   * @param int $sequence
   *   The position in the flow.
   */
  public function setActiveStep($sequence);

}

<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for pipeline step plugins.
 */
interface PipelineStepInterface extends PluginInspectionInterface {

  /**
   * Executes the business logic of the pipeline step.
   *
   * @param array $data
   *   An array of data to be passed to the execute method.
   *
   * @return null|array
   *   If no errors were encountered during the step execution, nothing should
   *   be returned. Return the error message as a render array.
   */
  public function execute(array &$data);

  /**
   * Gives a chances to plugins to perform some last tasks before executing.
   *
   * @param array $data
   *   An array of data to be passed to the execute method.
   *
   * @return $this
   */
  public function prepare(array &$data);

  /**
   * Sets the pipeline where this step is instantiated.
   *
   * @param \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline
   *   The pipeline plugin instance.
   *
   * @return $this
   */
  public function setPipeline(PipelinePipelineInterface $pipeline);

  /**
   * Gets the pipeline where this step belongs.
   *
   * @return \Drupal\pipeline\Plugin\PipelinePipelineInterface
   *   The pipeline plugin instance.
   */
  public function getPipeline();

  /**
   * Allows this step to react after the pipeline execution.
   *
   * This method should be used to perform cleanup for objects, storage entries
   * created by this step but required for the rest of the pipeline.
   */
  public function onPipelineSuccess();

  /**
   * Allows this step to react when the pipeline execution end with error.
   *
   * This method should be used to perform cleanup for objects, storage entries
   * created by this step but required for the rest of the pipeline.
   */
  public function onPipelineError();

}

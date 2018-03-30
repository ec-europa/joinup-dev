<?php

namespace Drupal\pipeline\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Defines an interface for pipeline step plugins.
 */
interface PipelineStepInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Execute the business logic of the pipeline step.
   *
   * @param array $data
   *   An array of data to be passed to the execute method.
   *
   * @return null|array|\Drupal\Component\Render\MarkupInterface|string
   *   If no errors were encountered during the step execution, nothing should
   *   be returned. Return the error message as a render array or a markup
   *   object or as a string.
   */
  public function execute(array &$data);

  /**
   * Allows this step to react after the pipeline execution.
   *
   * This method should be used to perform cleanup for objects, storage entries
   * created by this step but required for the rest of the pipeline.
   */
  public function onAfterPipelineExecute();

}

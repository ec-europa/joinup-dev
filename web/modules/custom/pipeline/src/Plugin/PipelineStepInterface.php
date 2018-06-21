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
   * @return null|array
   *   If no errors were encountered during the step execution, nothing should
   *   be returned. Return the error message as a render array.
   */
  public function execute();

  /**
   * Gives a chance to plugins to perform some tasks just before executing.
   *
   * @return null|array
   *   If no errors were encountered during the step preparation, nothing should
   *   be returned. Return the error message as a render array.
   */
  public function prepare();

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
   * Allows this step to react after the pipeline execution ends with success.
   */
  public function onPipelineSuccess();

  /**
   * Allows this step to react when the pipeline execution ends with an error.
   */
  public function onPipelineError();

  /**
   * Returns the persistent data.
   *
   * @return array
   *   The persistent data associative array.
   */
  public function getPersistentData();

  /**
   * Returns a single the persistent data entry, given its key.
   *
   * @param string $key
   *   The data key.
   *
   * @return mixed
   *   The persistent data entry.
   *
   * @throws \InvalidArgumentException
   *   If the data keyed as $key doesn't exist.
   */
  public function getPersistentDataValue($key);

  /**
   * Sets the persistent data.
   *
   * @param array $data
   *   The persistent data to be set.
   *
   * @return $this
   */
  public function setPersistentData(array $data);

  /**
   * Sets a value from the persistent data store.
   *
   * @param string $key
   *   The data key.
   * @param mixed $value
   *   The persistent data entry.
   *
   * @return $this
   */
  public function setPersistentDataValue($key, $value);

  /**
   * Clears the persistent data store.
   *
   * @return $this
   */
  public function clearPersistentData();

  /**
   * Un-sets a value from the persistent data store given its key.
   *
   * @param string $key
   *   The key of entry to be unset.
   *
   * @return $this
   */
  public function unsetPersistentDataValue($key);

}

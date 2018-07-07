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
   * @throws \Drupal\pipeline\Exception\PipelineStepExecutionLogicException
   *   By throwing this exception, the step notifies the pipeline orchestrator
   *   that he should exit the pipeline with an error, in this step. Method
   *   implementations should use PipelineStepLogicalException::setError() in
   *   order to pass the error markup that will be displayed to the user when
   *   the pipeline is exited.
   *
   * @see \Drupal\pipeline\Exception\PipelineStepExecutionLogicException::setError()
   */
  public function execute();

  /**
   * Gives a chance to step plugins to perform some tasks just before executing.
   *
   * @throws \Drupal\pipeline\Exception\PipelineStepPrepareLogicException
   *   By throwing this exception, the step prepare method notifies the pipeline
   *   orchestrator that he should exit the pipeline with an error, in this
   *   step. Method implementations should pass the error markup to be displayed
   *   to the user via PipelineStepPrepareLogicalException::setError().
   *
   * @see \Drupal\pipeline\Exception\PipelineStepPrepareLogicException::setError()
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
   * Checks if value exists in the persistent data store, given its key.
   *
   * @param string $key
   *   The data key.
   *
   * @return bool
   *   If the value exists in the persistent data store.
   */
  public function hasPersistentDataValue($key);

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

  /**
   * Returns the generic title to be used on pages.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The page title.
   */
  public function getPageTitle();

}

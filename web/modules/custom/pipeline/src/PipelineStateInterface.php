<?php

namespace Drupal\pipeline;

/**
 * Provides the contract for a pipeline state object.
 */
interface PipelineStateInterface {

  /**
   * Sets the step ID.
   *
   * @param string $step_id
   *   The step ID.
   *
   * @return $this
   */
  public function setStepId($step_id);

  /**
   * Returns the step ID.
   *
   * @return string
   *   The step ID.
   */
  public function getStepId();

  /**
   * Sets the data.
   *
   * @param array $data
   *   An arbitrary associative array with data.
   *
   * @return $this
   */
  public function setData(array $data);

  /**
   * Sets the value for given data entry.
   *
   * @param string $key
   *   The key of entry to be set.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   */
  public function setDataValue($key, $value);

  /**
   * Returns the state data.
   *
   * @return array
   *   The data array.
   */
  public function getData();

  /**
   * Checks if a value exists in the persistent data store.
   *
   * @param string $key
   *   The key to be checked.
   *
   * @return bool
   *   If the persistent data store contains a value with $key key.
   */
  public function hasDataValue($key);

  /**
   * Returns a value from the persistent data store given its key.
   *
   * @param string $key
   *   The key of entry to be returned.
   *
   * @return mixed
   *   The value.
   *
   * @throws \InvalidArgumentException
   *   If the data keyed as $key doesn't exist.
   */
  public function getDataValue($key);

  /**
   * Clears the persistent data store.
   *
   * @return $this
   */
  public function clearData();

  /**
   * Un-sets a value from the persistent data store given its key.
   *
   * @param string $key
   *   The key of entry to be unset.
   *
   * @return $this
   */
  public function unsetDataValue($key);

  /**
   * Checks if a value, identified by a key, exists in the batch sandbox.
   *
   * @param string $key
   *   The key to be checked.
   *
   * @return bool
   *   If the batch sandbox contains a value with $key key.
   */
  public function hasBatchValue($key);

  /**
   * Returns a value from the batch process sandbox.
   *
   * @param string $key
   *   The key of entry to be returned.
   *
   * @return mixed
   *   The value.
   *
   * @throws \InvalidArgumentException
   *   If the data keyed as $key doesn't exist.
   */
  public function getBatchValue($key);

  /**
   * Sets a data value in the batch process sandbox.
   *
   * @param string $key
   *   The key under where to store the value.
   * @param mixed $value
   *   The value to be stored.
   *
   * @return $this
   */
  public function setBatchValue($key, $value);

  /**
   * Un-sets a value from the batch sandbox given its key.
   *
   * @param string $key
   *   The key of entry to be unset.
   *
   * @return $this
   */
  public function unsetBatchValue($key);

  /**
   * Sets the batch total estimated iterations value.
   *
   * @param int $total_estimated_iterations
   *   The batch total estimated iterations.
   *
   * @return $this
   */
  public function setBatchProcessEstimatedIterations($total_estimated_iterations);

  /**
   * Returns the batch process total estimated iterations.
   *
   * @return int
   *   The batch total estimated iterations.
   */
  public function getBatchProcessEstimatedIterations();

  /**
   * Advances to the next batch.
   *
   * @return $this
   */
  public function advanceToNextBatch();

  /**
   * Returns the batch sequence.
   *
   * @return int
   *   The batch sequence.
   */
  public function getBatchProcessSequence();

  /**
   * Checks if the batch process has started.
   *
   * @return bool
   *   If the batch process was initialized and has started.
   */
  public function batchProcessIsStarted();

  /**
   * Resets the batch internals.
   *
   * @return $this
   */
  public function resetBatchProcess();

  /**
   * Collects an error message or messages produced by a batch.
   *
   * If a batch, running inside a step, is producing errors, we don't stop the
   * batch process, rather we collect them. Then, at the end of the batch
   * process, we assemble them in a single error message render array, in
   * PipelineStepWithBatchInterface::buildProcessErrorMessage() and we display
   * them all together, once. This method is used to collect batch errors.
   *
   * @param array|null $error_message
   *   The message as render array.
   *
   * @return $this
   */
  public function addBatchProcessErrorMessage(array $error_message = NULL);

  /**
   * Returns the list of error messages collected across batch process run.
   *
   * @return array
   *   A list of error messages, each one being a render array.
   */
  public function getBatchProcessErrorMessages();

  /**
   * Checks if error were reported during the batch process.
   *
   * @return bool
   *   If any logic error has been reported while running the batch process.
   */
  public function hasBatchProcessErrors();

}

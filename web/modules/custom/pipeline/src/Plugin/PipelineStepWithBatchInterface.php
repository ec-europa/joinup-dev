<?php

namespace Drupal\pipeline\Plugin;

/**
 * Interface for batch-aware step plugins.
 */
interface PipelineStepWithBatchInterface extends PipelineStepInterface {

  /**
   * Initializes a batch process.
   *
   * Pipeline steps using this interface will implement this method in order to
   * perform the "one time" tasks when the step is executed from the first time.
   * Also, in this phase, this step method should estimate the maximum number of
   * iterations needed to complete the batch process. Note that the batch
   * process is not ended based on this value. Instead, the batch process is
   * completed only by evaluating the ::batchProcessIsCompleted() method. For
   * this reason a batch process might run more iterations than were estimated
   * in this method, but the value is used to show the progress to the user.
   *
   * @return int
   *   The estimated number of iterations.
   */
  public function initBatchProcess();

  /**
   * Allows the step to decide when to end the batch process.
   *
   * @return bool
   *   Whether the batch process ended.
   */
  public function batchProcessIsCompleted();

  /**
   * Allows the step plugin to run some tasks after the batch process was ended.
   *
   * This runs after ::batchProcessIsCompleted() and the batch sandbox is still
   * available at this point.
   *
   * @return $this
   */
  public function onBatchProcessCompleted();

  /**
   * Sets a value in the batch sandbox.
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
   * Returns the list of error messages collected across batch process run.
   *
   * @return array
   *   A list of error messages, each one being a render array.
   */
  public function getBatchErrorMessages();

  /**
   * Builds the error message.
   *
   * If the step is producing errors in subsequent batches, we don't exit the
   * batch process. Rather we collect the error messages. This method is used to
   * aggregate all the messages collected across the batches, each of them being
   * render arrays, in a single render array.
   *
   * @return array
   *   The error message as a render array.
   */
  public function buildBatchProcessErrorMessage();

}

<?php

namespace Drupal\pipeline;

/**
 * Represents a state object.
 */
class PipelineState implements PipelineStateInterface {

  /**
   * The step ID.
   *
   * @var string
   */
  protected $stepId;

  /**
   * State data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * The sandbox data store used during one batch process.
   *
   * @var array
   */
  protected $batchData = [];

  /**
   * Batch current sequence.
   *
   * @var int
   */
  protected $batchCurrentSequence = 0;

  /**
   * Batch total estimated iterations.
   *
   * @var int
   */
  protected $batchTotalEstimatedIterations = 1;

  /**
   * Errors collected across batches.
   *
   * @var array
   */
  protected $errorMessages = [];

  /**
   * {@inheritdoc}
   */
  public function setStepId($step_id) {
    $this->stepId = $step_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStepId() {
    return $this->stepId;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDataValue($key, $value) {
    $this->data[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDataValue($key) {
    return array_key_exists($key, $this->data);
  }

  /**
   * {@inheritdoc}
   */
  public function getDataValue($key) {
    if (!array_key_exists($key, $this->data)) {
      throw new \InvalidArgumentException("There's no '$key' key in state data.");
    }
    return $this->data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function clearData() {
    $this->data = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetDataValue($key) {
    unset($this->data[$key]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasBatchValue($key) {
    return array_key_exists($key, $this->batchData);
  }

  /**
   * {@inheritdoc}
   */
  public function setBatchValue($key, $value) {
    $this->batchData[$key] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchValue($key) {
    if (!array_key_exists($key, $this->batchData)) {
      throw new \InvalidArgumentException("There's no '$key' key in the batch sandbox.");
    }
    return $this->batchData[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function unsetBatchValue($key) {
    unset($this->batchData[$key]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBatchTotalEstimatedIterations($total_estimated_iterations) {
    $this->batchTotalEstimatedIterations = $total_estimated_iterations;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchTotalEstimatedIterations() {
    return $this->batchTotalEstimatedIterations;
  }

  /**
   * {@inheritdoc}
   */
  public function advanceToNextBatch() {
    $this->batchCurrentSequence++;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchCurrentSequence() {
    return $this->batchCurrentSequence;
  }

  /**
   * {@inheritdoc}
   */
  public function resetBatch() {
    $this->batchData = [];
    $this->batchTotalEstimatedIterations = 1;
    $this->batchCurrentSequence = 0;
    $this->errorMessages = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addBatchErrorMessage(array $error_message = NULL) {
    $this->errorMessages[] = $error_message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchErrorMessages() {
    return $this->errorMessages;
  }

}

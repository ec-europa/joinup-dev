<?php

namespace Drupal\pipeline;

/**
 * Keeps track of the ongoing batch.
 *
 * @package Drupal\pipeline
 */
class PipelineStepBatchProgress implements PipelineStepBatchProgressInterface {

  protected $data;

  protected $totalSteps = 1;

  protected $currentStep = 0;

  protected $completed = FALSE;

  protected $needsInitialisation = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getCompleted(): bool {
    return $this->completed;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleted($completed = TRUE) {
    $this->completed = $completed;
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
  public function setData($data): void {
    $this->data = $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getTotalBatchIterations(): int {
    return $this->totalSteps;
  }

  /**
   * {@inheritdoc}
   */
  public function setTotalBatchIterations(int $totalSteps) {
    $this->totalSteps = $totalSteps;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchIteration(): int {
    return $this->currentStep;
  }

  /**
   * {@inheritdoc}
   */
  public function setBatchIteration(int $currentIteration) {
    $this->currentStep = $currentIteration;
  }

  /**
   * {@inheritdoc}
   */
  public function needsInitialisation(): bool {
    return $this->needsInitialisation;
  }

  /**
   * Persisted objects are already initialised.
   */
  public function __wakeup() {
    $this->needsInitialisation = FALSE;
  }

}
